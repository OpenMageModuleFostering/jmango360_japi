<?php

/**
 * Copyright 2017 JMango360
 */
class Jmango360_Japi_Adminhtml_Japi_PaymentController extends Mage_Adminhtml_Controller_Action
{
    const PAYPAL_SANDBOX_URL = 'https://api.sandbox.paypal.com/v1/oauth2/token';
    const PAYPAL_LIVE_URL = 'https://api.paypal.com/v1/oauth2/token';

    /**
     * Test Paypal API Credentials
     * Ref: https://developer.paypal.com/docs/integration/direct/make-your-first-call/
     */
    public function testPaypalAction()
    {
        $clientId = $this->getRequest()->getParam('client_id');
        $clientSecret = $this->getRequest()->getParam('client_secret');
        $sandbox = $this->getRequest()->getParam('sandbox', 1);

        if (!$clientId || !$clientSecret) {
            $data = array(
                'error' => 1,
                'message' => $this->__('Authentication credentials not valid')
            );
        } else {
            try {
                /* @var $model Jmango360_Japi_Model_Payment_Paypal */
                $model = Mage::getSingleton('japi/payment_paypal');
                $accessToken = $model->getAccessToken($clientId, $clientSecret, $sandbox);
                if ($accessToken) {
                    $data = array(
                        'success' => 1,
                        'message' => 'OK'
                    );
                } else {
                    $data = array(
                        'error' => 1,
                        'message' => $this->__('Some errors occurred, please try again')
                    );
                }
            } catch (Exception $e) {
                $data = array(
                    'error' => 1,
                    'message' => sprintf('[%s] %s', $e->getCode(), $e->getMessage())
                );
                Mage::logException($e);
            }
        }

        $this->getResponse()->setHeader('Content-Type', 'application/json');
        return $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($data));
    }

    protected function _getPaypalApiEndpoint($sandbox)
    {
        return $sandbox ? self::PAYPAL_SANDBOX_URL : self::PAYPAL_LIVE_URL;
    }
}
