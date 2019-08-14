<?php

class Jmango360_Japi_Model_Rest_Checkout_Redirect extends Jmango360_Japi_Model_Rest_Checkout
{
    public function getPaymentRedirect()
    {
        $data = array();

        $lastOrderId = $this->_getRequest()->getParam('last_order_id');
        if ($this->getCheckout()->getLastOrderId() != $lastOrderId) {
            throw new Jmango360_Japi_Exception(Mage::helper('japi')->__('Request info not matches the order.'), Jmango360_Japi_Model_Request::HTTP_INTERNAL_ERROR);
        }

        $sid = Mage::getSingleton('core/session')->getSessionId();
        $paymentUrl = $this->_getRequest()->getParam('payment_url');
        if (empty($paymentUrl)) {
            throw new Jmango360_Japi_Exception(Mage::helper('japi')->__('No payment provider to redirect found.'), Jmango360_Japi_Model_Request::HTTP_INTERNAL_ERROR);
        }

        $this->_getSession()->setRedirectToPaymentIsActive(true);

        $data['iframe'] = "<iframe width=\"100%\" height=\"100%\" src=\"{$paymentUrl}?SID={$sid}\"></iframe>";

        return $data;
    }
}
