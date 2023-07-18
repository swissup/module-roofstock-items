<?php

namespace Swissup\RoofstockItems\Block\Customer;

use Magento\Backend\Block\Template\Context;
use Magento\Framework\View\Element\Template;
use Swissup\RoofstockItems\Helper\Email as HelperEmail;

class UpdateCartQty extends Template
{
    /**
     * @var Swissup\RoofstockItems\Helper\Email
     */
    protected $helper;

    /**
     * @param Context $context
     * @param HelperEmail $helper
     * @param array $data = []
     */
    public function __construct(
        Context $context,
        HelperEmail $helper,
        array $data = [])
    {
        $this->helper = $helper;
        parent::__construct($context, $data);
    }

    /**
     * Gets the isEnabled.
     *
     * @return {Bool}
     */
    public function getIsEnabled()
    {
        return $this->helper->isEnabled();
    }
}
