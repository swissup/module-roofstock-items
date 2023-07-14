<?php

namespace Swissup\RoofstockItems\Model;

use Magento\CatalogInventory\Api\StockRegistryInterface as StockRegistry;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;
use Magento\InventorySalesApi\Api\GetProductSalableQtyInterface;
use Magento\InventorySalesApi\Api\StockResolverInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Backend\Helper\Data;
use Magento\Store\Model\StoreManagerInterface;

class StockStatus extends \Magento\Framework\Model\AbstractModel
{
    /**
     * @var integer
     */
    protected $storeId = 0;

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
     * @param Data $helper
     * @param StoreManagerInterface $storeManager
     * @param StockRegistry $stockRegistry
     */
    public function __construct(
        Data $helper,
        StoreManagerInterface $storeManager,
        StockRegistry $stockRegistry
    ){
        $this->helper = $helper;
        $this->storeManager = $storeManager;
        $this->stockRegistry = $stockRegistry;
    }

    /**
     * @param {Object} $product
     * @return {Boolean}
     */
    protected function getStockStatus($product)
    {
        $objectManager = ObjectManager::getInstance();
        $websiteCode = $this->storeManager->getWebsite()->getCode();

        if ($this->helper->isModuleOutputEnabled('Magento_InventorySales')) {
            $stockResolver = $objectManager->get(StockResolverInterface::class);
            $productSalableQty = $objectManager->get(GetProductSalableQtyInterface::class);

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
     * Sets the store identifier.
     *
     * @param Integer $toreId
     * @return $this
     */
    public function setStoreId($storeId)
    {
        $this->setStoreId = $storeId;
        return $this;
    }

    /**
     * Gets the store identifier.
     *
     * @return Integer
     */
    public function getStoreId()
    {
        return $this->storeId;
    }
}
