<?php
namespace Swissup\RoofstockItems\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Quote\Model\Quote\Item as QuoteItem;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Framework\Message\ManagerInterface;
use Psr\Log\LoggerInterface as Logger;
use Swissup\RoofstockItems\Model\StockStatus as ProductStockStatus;

class RemoveOutOfStockItems implements ObserverInterface
{
    /**
     *  @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;

    /**
     *  @var \Magento\Quote\Model\Quote\Item
     */
    protected $quoteItem;

    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    protected $quoteRepository;

    /**
     *  @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     *  @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;

    /**
     * @param   CheckoutSession $checkoutSession
     * @param   QuoteItem $quoteItem
     * @param   CartRepositoryInterface $quoteRepository
     * @param   ManagerInterface $messageManager
     * @param   ProductStockStatus $productStockStatus
     * @param   Logger $logger
     */
    public function __construct(
        CheckoutSession $checkoutSession,
        QuoteItem $quoteItem,
        CartRepositoryInterface $quoteRepository,
        ManagerInterface $messageManager,
        ProductStockStatus $productStockStatus,
        Logger $logger
    ) {
        $this->checkoutSession      = $checkoutSession;
        $this->quoteItem            = $quoteItem;
        $this->quoteRepository      = $quoteRepository;
        $this->messageManager       = $messageManager;
        $this->productStockStatus   = $productStockStatus;
        $this->logger               = $logger;
    }

    /**
     * @param  Observer  $observer
     */
    public function execute(Observer $observer)
    {
        $quote = $this->checkoutSession->getQuote();
        $items = $quote->getAllVisibleItems();

        foreach ($items as $item) {
            try {
                if ($item->getProduct()->getTypeId() === 'configurable' ||
                    $item->getProduct()->getTypeId() === 'grouped'
                ) {
                    if (!$this->productStockStatus->getStockStatus($item)) {
                        $this->removeProduct($item);
                    }

                } else {
                    /* simple products */
                    $stockItem = $item->getProduct()->getExtensionAttributes()->getStockItem();
                    $stockStatus = $stockItem->getIsInStock();

                    if (!$stockStatus) {
                        $this->removeProduct($item);
                    }
                }
            } catch (Exception $e) {
                $this->logger->error($e->getMessage());
            }
        }

        /* update quote repository */
        $this->quoteRepository->save($quote);

        return $this;
    }

    /**
     * remove Out-Of-Stock products from customer quote
     *
     * @param {Object} $product
     * @return void
     */
    protected function removeProduct($product)
    {
        $quoteItem = $this->quoteItem->load($product->getId());
        $quoteItem->delete();

        $message = __("The Out-Of-Stock product with SKU-" . $product->getSku() . " was removed from your cart.");
        $this->messageManager->addNotice($message);
    }
}
