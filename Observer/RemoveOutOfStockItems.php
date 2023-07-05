<?php
namespace Swissup\RoofstockItems\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Quote\Model\Quote\Item as QuoteItem;
use Magento\Framework\Message\ManagerInterface;
use Psr\Log\LoggerInterface as Logger;

class RemoveOutOfStockItems implements ObserverInterface
{
    protected $checkoutSession;
    protected $quoteItem;
    protected $logger;
    protected $messageManager;

    /**
     * @param   CheckoutSession $checkoutSession
     * @param   CustomerSession $customerSession
     * @param   QuoteItem $quoteItem
     * @param   ManagerInterface $messageManager
     * @param   Logger $logger
     */
    public function __construct(
        CheckoutSession $checkoutSession,
        CustomerSession $customerSession,
        QuoteItem $quoteItem,
        ManagerInterface $messageManager,
        Logger $logger
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->customerSession = $customerSession;
        $this->quoteItem = $quoteItem;
        $this->messageManager = $messageManager;
        $this->logger = $logger;
    }

    /**
     * @param  Observer  $observer
     */
    public function execute(Observer $observer)
    {
        if (!$this->customerSession->isLoggedIn()) {
            return $this;
        }

        $quote = $this->checkoutSession->getQuote();
        $items = $quote->getAllVisibleItems();

        foreach ($items as $item) {
            try {
                $stockItem = $item->getProduct()->getExtensionAttributes()->getStockItem();
                $stockStatus = $stockItem->getIsInStock();

                if ($item->getProduct()->getTypeId() === 'configurable' ||
                    $item->getProduct()->getTypeId() === 'grouped'
                ) {
                    $stockStatus = $stockItem->getData('stock_status_changed_auto');
                }

                if (!$stockStatus) {
                    $quoteItem = $this->quoteItem->load($item->getId());
                    $quoteItem->delete(); // remove Out-Of-Stock item from customer quote

                    $message = __("The Out-Of-Stock product with SKU-" . $item->getSku() . " was removed from your cart.");
                    $this->messageManager->addNotice($message);
                }
            } catch (Exception $e) {
                $this->logger->error($e->getMessage());
            }
        }

        return $this;
    }
}
