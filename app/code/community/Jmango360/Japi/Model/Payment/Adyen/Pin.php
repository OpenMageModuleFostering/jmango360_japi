<?php

/**
 * Copyright 2017 JMango360
 */
if (@class_exists('Adyen_Payment_Model_Adyen_Abstract')) {
    class Jmango360_Japi_Model_Payment_Adyen_Abstract extends Adyen_Payment_Model_Adyen_Abstract
    {
    }
} else {
    class Jmango360_Japi_Model_Payment_Adyen_Abstract extends Jmango360_Japi_Model_Payment
    {
    }
}

class Jmango360_Japi_Model_Payment_Adyen_Pin extends Jmango360_Japi_Model_Payment_Adyen_Abstract
{
    protected $_code = 'jmango_payment_adyen_pin';
    protected $_formBlockType = 'japi/payment_form';
    protected $_infoBlockType = 'japi/payment_info';

    /**
     * Retrieve payment method title
     * Return title from additinal information if provided
     *
     * @return string
     */
    public function getTitle()
    {
        try {
            $paymentInfo = $this->getInfoInstance();
            $title = $paymentInfo->getAdditionalInformation('title');
            if ($title) return $title;
        } catch (Exception $e) {
        }

        return parent::getTitle();
    }

    /**
     * Only enable this payment in API call
     *
     * @param null $quote
     * @return bool
     */
    public function isAvailable($quote = null)
    {
        return false;//Mage::app()->getRequest()->getModuleName() == 'japi';
    }

    /**
     * Validate payment transaction by mobile app
     *
     * @return $this
     */
    public function validate()
    {
        return $this;
    }

    /**
     * Return Order place redirect url
     *
     * @return string
     */
    public function getOrderPlaceRedirectUrl()
    {
        return Mage::getUrl('japi/adyen/pin', array('_secure' => true));
    }
}
