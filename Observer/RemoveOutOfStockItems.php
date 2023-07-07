<?php
namespace Swissup\RoofstockItems\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Quote\Model\Quote\Item as QuoteItem;
use Magento\Framework\Message\ManagerInterface;
use Magento\CatalogInventory\Api\StockRegistryInterface as StockRegistry;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;
use Magento\InventorySalesApi\Api\GetProductSalableQtyInterface;
use Magento\InventorySalesApi\Api\StockResolverInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Backend\Helper\Data;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface as Logger;

class RemoveOutOfStockItems implements ObserverInterface
{
    /**
     *  @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;

    /**
     *  @var \Magento\Customer\Model\Session
     */
    protected $customerSession

    /**
     *  @var \Magento\Quote\Model\Quote\Item
     */
    protected $quoteItem;

    /**
     *  @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     *  @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;

    /**
     *  @var \Magento\Backend\Helper\Data
     */
    protected $helper;

    /**
     *  @var \Magento\CatalogInventory\Api\StockRegistryInterface
     */
    protected $stockRegistry;

    /**
     *  @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * @param   CheckoutSession $checkoutSession
     * @param   CustomerSession $customerSession
     * @param   QuoteItem $quoteItem
     * @param   ManagerInterface $messageManager
     * @param   Data $helper
     * @param   Logger $logger
     * @param   StockRegistry $stockRegistry
     * @param   StoreManagerInterface $storeManager
     */
    public function __construct(
        CheckoutSession $checkoutSession,
        CustomerSession $customerSession,
        QuoteItem $quoteItem,
        ManagerInterface $messageManager,
        Data $helper,
        Logger $logger,
        StockRegistry $stockRegistry,
        StoreManagerInterface $storeManager
    ) {
        $this->checkoutSession  = $checkoutSession;
        $this->customerSession  = $customerSession;
        $this->quoteItem        = $quoteItem;
        $this->messageManager   = $messageManager;
        $this->helper           = $helper;
        $this->logger           = $logger;
        $this->stockRegistry    = $stockRegistry;
        $this->storeManager     = $storeManager;
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
                if ($item->getProduct()->getTypeId() === 'configurable' ||
                    $item->getProduct()->getTypeId() === 'grouped'
                ) {
                    if (!$this->getStockStatus($item)) {
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

        return $this;
    }

    /**
     * @param {Object} $product
     * @return {Boolean}
     */
    protected function getStockStatus($product)
    {
        if ($this->helper->isModuleOutputEnabled('Magento_InventorySales')) {
            $objectManager = ObjectManager::getInstance();
            $stockResolver = $objectManager->get(StockResolverInterface::class);
            $productSalableQty = $objectManager->get(GetProductSalableQtyInterface::class);

            $websiteCode = $this->storeManager->getWebsite()->getCode();
            $stock = $stockResolver->execute(SalesChannelInterface::TYPE_WEBSITE, $websiteCode);
            $qty = $productSalableQty->execute($product->getSku(), $stock->getStockId());

            if (!$qty) {
                return false;
            }

            return true;

        } else {
            // code...
        }
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
