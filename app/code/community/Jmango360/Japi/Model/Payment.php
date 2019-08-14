<?php

/**
 * Copyright 2017 JMango360
 */
class Jmango360_Japi_Model_Payment extends Mage_Payment_Model_Method_Abstract
{
    const CODE = 'jmango_payment';
    const PAYMENT_ID = 'id';
    const PAYMENT_TITLE = 'title';
    const PAYPAL_PAYMENT_ID = 'payment_id';

    protected $_code = self::CODE;
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
            $title = $paymentInfo->getAdditionalInformation(self::PAYMENT_TITLE);
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
        return Mage::app()->getRequest()->getModuleName() == 'japi' &&
            Mage::app()->getRequest()->getControllerName() != 'checkout';
    }

    /**
     * Validate payment transaction by mobile app
     * Used for: Paypal
     *
     * @return $this
     */
    public function validate()
    {
        $paymentInfo = $this->getInfoInstance();
        switch ($paymentInfo->getAdditionalInformation(self::PAYMENT_ID)) {
            case 'paypal':
                $this->_validatePaypal();
                break;
            case 'braintree':
                $this->_validateBraintree();
                break;
        }
        return $this;
    }

    /**
     * Validate Braintree transation from Orchard server
     */
    protected function _validateBraintree()
    {
        return true;
    }

    /**
     * Validate Paypal payment ID sent from mobile
     *
     * @throws Jmango360_Japi_Exception
     */
    protected function _validatePaypal()
    {
        $paymentInfo = $this->getInfoInstance();
        $paymentId = $paymentInfo->getAdditionalInformation(self::PAYPAL_PAYMENT_ID);
        if ($paymentId) {
            /* @var $model Jmango360_Japi_Model_Payment_Paypal */
            $model = Mage::getSingleton('japi/payment_paypal');
            if ($model->verifyPayment($paymentId)) {
                /* @var $session Mage_Checkout_Model_Session */
                $session = Mage::getSingleton('checkout/session');
                $session->setData('jmango_payment_paypal_verified', true);
                $session->setData('place_order', true);
            }
        } else {
            throw new Jmango360_Japi_Exception(
                Mage::helper('japi')->__('Paypal Payment ID not found'),
                Jmango360_Japi_Model_Request::HTTP_INTERNAL_ERROR
            );
        }
    }
}
