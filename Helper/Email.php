<?php

namespace Swissup\RoofstockItems\Helper;

use Magento\Framework\App\Area;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\Mail\Template\TransportBuilder;

class Email extends AbstractHelper
{
    /**
     * @var string
     */
    const CONFIG_PATH_ENABLED = 'roofstock_items/general/enabled';
    const CONFIG_PATH_EMAIL_ENABLED = 'roofstock_items/email_admin/enabled';
    const CONFIG_PATH_EMAIL_SEND_FROM = 'roofstock_items/email_admin/send_from';
    const CONFIG_PATH_EMAIL_SEND_TO = 'roofstock_items/email_admin/send_to';
    const CONFIG_PATH_EMAIL_TEMPLATE = 'roofstock_items/email_admin/template';

    /**
     * @var  Magento\Framework\Mail\Template\TransportBuilder
     */
    protected $transportBuilder;

    /**
     * Constructor
     *
     * @param Context $context
     * @param TransportBuilder $transportBuilder
     */
    public function __construct(
        Context $context,
        TransportBuilder $transportBuilder
    ){
        $this->transportBuilder = $transportBuilder;
        parent::__construct($context);
    }
    
    /**
     * Sends an email.
     * @param Array $params
     * @param $storeId
     */
    public function sendEmail($params = [], $storeId = null)
    {
        if (!$this->isEnabled() && !$this->isEmailEnabled()) {
            return;
        }

        try {
            $sendToEmails = $this->getSendTo();
            $sender = $this->getSender();
            $templateId = $this->getTemplate();

            foreach ($sendToEmails as $email) {
                $transport = $this->transportBuilder
                    ->setTemplateIdentifier($templateId)
                    ->setTemplateOptions(['area' => Area::AREA_FRONTEND, 'store' => $storeId])
                    ->setTemplateVars($params)
                    ->setFrom($sender)
                    ->addTo($email)
                    ->getTransport();

                $transport->sendMessage();
            }

        } catch (\Magento\Framework\Exception\MailException $e) {
            $this->logger->error($e->getMessage);
        }
    }

    /**
     * Determines if enabled.
     * @return {Bool}
     */
    public function isEnabled()
    {
        return $this->scopeConfig->getValue(self::CONFIG_PATH_ENABLED);
    }

    /**
     * Determines if email enabled.
     * @return {Bool}
     */
    public function isEmailEnabled()
    {
        return $this->scopeConfig->getValue(self::CONFIG_PATH_EMAIL_ENABLED);
    }

    /**
     * Gets the sender.
     * @return {String}
     */
    public function getSender()
    {
        return $this->scopeConfig->getValue(self::CONFIG_PATH_EMAIL_SEND_FROM);
    }

    /**
     * Gets the send to.
     * @return {Array}
     */
    public function getSendTo()
    {
        $emails = $this->scopeConfig->getValue(self::CONFIG_PATH_EMAIL_SEND_TO);
        return explode(',', $emails);
    }

    /**
     * Gets the template.
     * @return {String}
     */
    public function getTemplate()
    {
        return $this->scopeConfig->getValue(self::CONFIG_PATH_EMAIL_TEMPLATE);
    }
}
