<?php

/**
 * Copyright 2017 JMango360
 */
class Jmango360_Japi_Model_Payment_Braintree
{
    const BRAINTREE_TRANSACTION_ID = 'transaction_id';

    public function __construct()
    {
        $braintreeLib = Mage::getBaseDir('lib') . '/Jmango360/Braintree/lib/Braintree.php';
        if (file_exists($braintreeLib)) {
            require_once $braintreeLib;
        } else {
            throw new Jmango360_Japi_Exception(
                Mage::helper('japi')->__('The Braintree PHP SDK missing', phpversion()),
                Jmango360_Japi_Model_Request::HTTP_INTERNAL_ERROR
            );
        }

        if (version_compare(phpversion(), '5.4', '<')) {
            throw new Jmango360_Japi_Exception(
                Mage::helper('japi')->__('The Braintree PHP SDK requires PHP version 5.4.0 or higher, currently %s', phpversion()),
                Jmango360_Japi_Model_Request::HTTP_INTERNAL_ERROR
            );
        }

        if (!extension_loaded('curl')) {
            throw new Jmango360_Japi_Exception(
                Mage::helper('japi')->__('The Braintree PHP SDK requires cURL extension'),
                Jmango360_Japi_Model_Request::HTTP_INTERNAL_ERROR
            );
        }

        $this->_setup();
    }

    protected function _setup()
    {
        try {
            $environment = Mage::getStoreConfig('japi/jmango_rest_braintree_settings/sandbox') ? 'sandbox' : 'production';
            Braintree_Configuration::environment($environment);
            if ($environment != 'sandbox') {
                Braintree_Configuration::merchantId(Mage::getStoreConfig('japi/jmango_rest_braintree_settings/merchant_id'));
                Braintree_Configuration::publicKey(Mage::getStoreConfig('japi/jmango_rest_braintree_settings/public_key'));
                Braintree_Configuration::privateKey(Mage::getStoreConfig('japi/jmango_rest_braintree_settings/private_key'));
            } else {
                Braintree_Configuration::merchantId(Mage::getStoreConfig('japi/jmango_rest_braintree_settings/sandbox_merchant_id'));
                Braintree_Configuration::publicKey(Mage::getStoreConfig('japi/jmango_rest_braintree_settings/sandbox_public_key'));
                Braintree_Configuration::privateKey(Mage::getStoreConfig('japi/jmango_rest_braintree_settings/sandbox_private_key'));
            }
        } catch (\Exception $e) {
            throw new Jmango360_Japi_Exception(
                $e->getMessage(),
                Jmango360_Japi_Model_Request::HTTP_INTERNAL_ERROR
            );
        }
    }

    /**
     * @param string $transactionId
     * @return Braintree_Transaction
     * @throws Exception
     */
    public function getTransaction($transactionId)
    {
        try {
            return Braintree_Transaction::find($transactionId);
        } catch (\Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
}