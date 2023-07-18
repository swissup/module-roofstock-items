<?php
namespace Swissup\RoofstockItems\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Exception\LocalizedException;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Quote\Model\Quote\Item as QuoteItem;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Framework\Message\ManagerInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface as Logger;
use Swissup\RoofstockItems\Model\StockStatus as ProductStockStatus;
use Swissup\RoofstockItems\Helper\Email as HelperEmail;

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
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    protected $storeManager;

    /**
     *  @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     *  @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;

    /**
     * @var  \Swissup\RoofstockItems\Model\StockStatus
     */
    protected $productStockStatus;

    /**
     *  @var \Swissup\RoofstockItems\Helper\Email
     */
    protected $helper;

    /**
     * @param   CheckoutSession $checkoutSession
     * @param   QuoteItem $quoteItem
     * @param   CartRepositoryInterface $quoteRepository
     * @param   ManagerInterface $messageManager
     * @param   ProductStockStatus $productStockStatus
     * @param   StoreManagerInterface $storeManager
     * @param   Logger $logger
     * @param   HelperEmail $helper
     */
    public function __construct(
        CheckoutSession $checkoutSession,
        QuoteItem $quoteItem,
        CartRepositoryInterface $quoteRepository,
        ManagerInterface $messageManager,
        ProductStockStatus $productStockStatus,
        StoreManagerInterface $storeManager,
        Logger $logger,
        HelperEmail $helper
    ) {
        $this->checkoutSession      = $checkoutSession;
        $this->quoteItem            = $quoteItem;
        $this->quoteRepository      = $quoteRepository;
        $this->messageManager       = $messageManager;
        $this->productStockStatus   = $productStockStatus;
        $this->storeManager         = $storeManager;
        $this->logger               = $logger;
        $this->helper               = $helper;
    }

    /**
     * @param  Observer  $observer
     */
    public function execute(Observer $observer)
    {
        if (!$this->helper->isEnabled()) {
            return;
        }

        $quote = $this->checkoutSession->getQuote();
        $items = $quote->getAllVisibleItems();
        $outOfStockItems = [];

        foreach ($items as $item) {
            try {
                if ($item->getProduct()->getTypeId() === 'configurable' ||
                    $item->getProduct()->getTypeId() === 'grouped'
                ) {
                    if (!$this->productStockStatus->getStockStatus($item)) {
                        $this->removeProduct($item);
                        $outOfStockItems[] = $item->getSku();
                    }

                } else {
                    /* simple products */
                    $stockItem = $item->getProduct()->getExtensionAttributes()->getStockItem();
                    $stockStatus = $stockItem->getIsInStock();

                    if (!$stockStatus) {
                        $this->removeProduct($item);
                        $outOfStockItems[] = $item->getSku();
                    }
                }
            } catch (\Exception $e) {
                $this->logger->error($e->getMessage());
            }
        }

        $storeId = $this->productStockStatus->getStoreId();
        /* send email notification with products SKUs*/
        if ($outOfStockItems) {
            $this->helper->sendEmail($outOfStockItems, $storeId);
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
        $this->productStockStatus->setStoreId($this->storeManager->getStore()->getId());

        $message = __("The Out-Of-Stock product with SKU-" . $product->getSku() . " was removed from your cart.");
        $this->messageManager->addNotice($message);
    }
}
