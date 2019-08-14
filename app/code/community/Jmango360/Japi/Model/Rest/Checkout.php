<?php

class Jmango360_Japi_Model_Rest_Checkout extends Mage_Checkout_Model_Type_Onepage
{
    protected $_logname = 'rest_checkout.log';

    public function dispatch()
    {
        $action = $this->_getRequest()->getAction();
        $operation = $this->_getRequest()->getOperation();

        switch ($action . $operation) {
            case 'getCheckoutMethods' . Jmango360_Japi_Model_Request::OPERATION_RETRIEVE:
                $data = $this->_getCheckoutMethods();
                $this->_getResponse()->render($data);
                $this->_getResponse()->setHttpResponseCode(Jmango360_Japi_Model_Server::HTTP_OK);
                break;
            case 'updateCartAddresses' . Jmango360_Japi_Model_Request::OPERATION_UPDATE:
                $data = $this->_updateCartAddresses();
                $this->_getResponse()->render($data);
                $this->_getResponse()->setHttpResponseCode(Jmango360_Japi_Model_Server::HTTP_CREATED);
                break;
            case 'collectTotals' . Jmango360_Japi_Model_Request::OPERATION_CREATE:
                $data = $this->_checkout();
                $this->_getResponse()->render($data);
                $this->_getResponse()->setHttpResponseCode(Jmango360_Japi_Model_Server::HTTP_CREATED);
                break;
            case 'submitOrder' . Jmango360_Japi_Model_Request::OPERATION_CREATE:
                $data = $this->_submitOrder();
                $this->_getResponse()->render($data);
                $this->_getResponse()->setHttpResponseCode(Jmango360_Japi_Model_Server::HTTP_CREATED);
                break;
            case 'getPaymentRedirect' . Jmango360_Japi_Model_Request::OPERATION_CREATE:
                $data = $this->_paymentRedirect();
                $this->_getResponse()->render($data);
                $this->_getResponse()->setHttpResponseCode(Jmango360_Japi_Model_Server::HTTP_CREATED);
                break;
            case 'updateShippingMethod' . Jmango360_Japi_Model_Request::OPERATION_UPDATE:
                $data = $this->_updateShippingMethod();
                $this->_getResponse()->render($data);
                $this->_getResponse()->setHttpResponseCode(Jmango360_Japi_Model_Server::HTTP_OK);
                break;
            case 'updatePaymentMethod' . Jmango360_Japi_Model_Request::OPERATION_UPDATE:
                $data = $this->_updatePaymentMethod();
                $this->_getResponse()->render($data);
                $this->_getResponse()->setHttpResponseCode(Jmango360_Japi_Model_Server::HTTP_OK);
                break;
            case 'updateOrder' . Jmango360_Japi_Model_Request::OPERATION_UPDATE:
                $data = $this->_updateOrder();
                $this->_getResponse()->render($data);
                $this->_getResponse()->setHttpResponseCode(Jmango360_Japi_Model_Server::HTTP_OK);
                break;

            /**
             * After a one page checkout payment to any provider the return url goes to the:
             *  - onepage/checkout/success (case: success) = the only real successfull payment
             *  - onepage/checkout/failure (case: failure)
             *  - checkout/cart/index (case: index)
             *
             *  This can be used to tell the mobile App to show the success page, or another page based on the payment status
             */
            case 'success' . Jmango360_Japi_Model_Request::OPERATION_RETRIEVE:
                $data = $this->_paymentSuccessResponse();
                $this->_getResponse()->render($data);
                $this->_getResponse()->setHttpResponseCode(Jmango360_Japi_Model_Server::HTTP_OK);
                break;
            case 'failure' . Jmango360_Japi_Model_Request::OPERATION_RETRIEVE:
                $data = $this->_paymentFailureResponse();
                $this->_getResponse()->render($data);
                $this->_getResponse()->setHttpResponseCode(Jmango360_Japi_Model_Server::HTTP_OK);
                break;
            case 'index' . Jmango360_Japi_Model_Request::OPERATION_RETRIEVE:
                $data = $this->_paymentFailureResponse();
                $this->_getResponse()->render($data);
                $this->_getResponse()->setHttpResponseCode(Jmango360_Japi_Model_Server::HTTP_OK);
                break;

            default:
                throw new Jmango360_Japi_Exception(Mage::helper('japi')->__('Resource method not implemented'), Jmango360_Japi_Model_Request::HTTP_INTERNAL_ERROR);
                break;
        }
    }

    /*
     * Recurring profiles (like monthly subscriptions) are not supported through the mobile App
     */
    protected function _paymentSuccessResponse()
    {
        $data = array();

        $session = $this->_getSession();

        $lastSuccessQuoteId = $session->getLastSuccessQuoteId();
        $lastQuoteId = $session->getLastQuoteId();
        $lastOrderId = $session->getLastOrderId();
        $lastRealOrderId = $session->getLastRealOrderId();

        if (!$lastSuccessQuoteId || !$this->_getLastOrder($lastQuoteId, $lastOrderId, $lastRealOrderId)) {
            throw new Jmango360_Japi_Exception(
                Mage::helper('japi')->__('Something is wrong with the order.'),
                Jmango360_Japi_Model_Request::HTTP_INTERNAL_ERROR
            );
        }

        $lastOrderId = $session->getLastOrderId();
        $session->clear();
        Mage::dispatchEvent('checkout_onepage_controller_success_action', array('order_ids' => array($lastOrderId)));

        /* @var $order Mage_Sales_Model_Order */
        $order = Mage::getModel('sales/order')->load($lastOrderId);
        $data['order_id'] = $order->getIncrementId();
        $data['payment_status'] = 'success';
        $data['order_status'] = $order->getStatus();

        $this->_getSession()->unsRedirectToPaymentIsActive();
        $this->_getServer()->unsetIsRest();

        return $data;
    }

    protected function _paymentFailureResponse()
    {
        $this->_getServer()->unsetIsRest();
        $session = $this->_getSession();

        $errors = $session->getMessages()->getErrors();
        if (count($errors)) {
            $messages = array();
            foreach ($errors as $error) {
                if (is_object($error)) {
                    $messages[] = $error->getCode();
                }
            }
            if (count($messages)) {
                throw new Jmango360_Japi_Exception(
                    implode(',', $messages),
                    Jmango360_Japi_Model_Request::HTTP_INTERNAL_ERROR
                );
            }
        }

        $lastQuoteId = $session->getLastQuoteId();
        $lastOrderId = $session->getLastOrderId();
        $lastRealOrderId = $session->getLastRealOrderId();

        if (!$this->_getLastOrder($lastQuoteId, $lastOrderId, $lastRealOrderId)) {
            throw new Jmango360_Japi_Exception(
                Mage::helper('japi')->__('Something is wrong with the quote or order.'),
                Jmango360_Japi_Model_Request::HTTP_INTERNAL_ERROR
            );
        }

        $this->_reactivateQuote($lastQuoteId);

        /* @var $order Mage_Sales_Model_Order */
        $order = Mage::getModel('sales/order')->load($lastOrderId);
        $data['order_id'] = $order->getIncrementId();
        $data['payment_status'] = 'no_success';
        $data['order_status'] = $order->getStatus();

        $this->_getSession()->unsRedirectToPaymentIsActive();

        return $data;
    }

    protected function _getLastOrder($lastQuoteId, $lastOrderId, $lastRealOrderId)
    {
        $session = $this->_getSession();

        if ($lastRealOrderId) {
            $order = Mage::getModel('sales/order')->loadByIncrementId($lastRealOrderId);
            if (empty($lastQuoteId)) {
                $session->setLastQuoteId($order->getQuoteId());
            }
            if (empty($lastOrderId)) {
                $session->setLastOrderId($order->getId());
            }

            return true;
        }

        if ($lastOrderId) {
            $order = Mage::getModel('sales/order')->load($lastOrderId);
            if (empty($lastQuoteId)) {
                $session->setLastQuoteId($order->getQuoteId());
            }
            if (empty($lastRealOrderId)) {
                $session->setLastRealOrderId($order->getIncrementId());
            }

            return true;
        }

        return false;
    }

    protected function _reactivateQuote($quoteId)
    {
        $quote = Mage::getModel('sales/quote')->load($quoteId);
        $quote->setIsActive(true)->save();

        $this->_getSession()->replaceQuote($quote);

        if (Mage::getSingleton('customer/session')->isLoggedIn()) {
            $this->_getSession()->setCustomer(Mage::getSingleton('customer/session')->getCustomer());
        }

        return $this;
    }

    protected function _paymentRedirect()
    {
        /* @var $model Jmango360_Japi_Model_Rest_Checkout_Redirect */
        $model = Mage::getModel('japi/rest_checkout_redirect');
        $data = $model->getPaymentRedirect();

        return $data;
    }

    protected function _submitOrder()
    {
        $quote = $this->getCheckout()->getQuote();
        if (!$quote->getItemsCount()) {
            throw new Jmango360_Japi_Exception(
                Mage::helper('japi')->__('Cart is empty.'),
                Jmango360_Japi_Model_Request::HTTP_INTERNAL_ERROR
            );
        }

        $checkoutRedirectUrl = $this->getQuote()->getPayment()->getCheckoutRedirectUrl();
        if ($checkoutRedirectUrl) {
            return array(
                'session_id' => Mage::getSingleton('core/session')->getSessionId(),
                'online_payment' => true,
                'payment_url' => $this->_processPaymentUrl($checkoutRedirectUrl)
            );
        }

        /* @var $model Jmango360_Japi_Model_Rest_Checkout_Submit */
        $model = Mage::getModel('japi/rest_checkout_submit');
        $data = $model->submitOrder();

        return $data;
    }

    protected function _processPaymentUrl($url)
    {
        if (!$url) return '';

        if (strpos($url, 'SID') === false) {
            if (strpos($url, '?') === false) $url .= '?SID=' . Mage::getSingleton('core/session')->getSessionId();
            else $url .= '&SID=' . Mage::getSingleton('core/session')->getSessionId();
        }

        if (Mage::app()->getStore()->getId() != Mage::app()->getWebsite()->getDefaultStore()->getId()) {
            if (strpos($url, '?') === false) $url .= '?___store=' . Mage::app()->getStore()->getCode();
            else $url .= '&___store=' . Mage::app()->getStore()->getCode();
        }

        return $url;
    }

    protected function _updateCartAddresses()
    {
        $quote = $this->getCheckout()->getQuote();
        if (!$quote->getItemsCount()) {
            throw new Jmango360_Japi_Exception(Mage::helper('japi')->__('Cart is empty.'), Jmango360_Japi_Model_Request::HTTP_INTERNAL_ERROR);
        }

        /* @var $model Jmango360_Japi_Model_Rest_Checkout_Onepage */
        $model = Mage::getModel('japi/rest_checkout_onepage');
        $data = $model->updateCartAddresses2();

        Mage::getSingleton('core/session')->setData('is_address_update', true);

        return $data;
    }

    /*
     * Saving all data to the quote, colleting totals while writing prices, totals and costs to the quote
     * and return totals in the response
     * Multi shipping through the app is not implemented
    */
    protected function _checkout()
    {
        /*
         * Save all POST data for Buckaroo using later
         */
        $POST = $this->_getRequest()->getParam('form', array());
        $POST['payment']['method'] = $this->_getRequest()->getParam('payment_method');
        $this->_getSession()->setData('POST', $POST);

        $data = null;

        /* Check if is multi-shipping
        *   - (Multishipping changes behaviour using more than one shipping_method in "sales_flat_quote_address"
        *        and only than using items per shipping method in "sales_flat_quote_address_item")
        * Multi Shipping is not build in App yet (seldom used for standard use Magento)
        */
        if ($this->getCheckout()->getIsMultiShipping()) {
            throw new Jmango360_Japi_Exception(Mage::helper('japi')->__('Multishipping not implemented yet.'), Jmango360_Japi_Model_Request::HTTP_INTERNAL_ERROR);
        }

        $quote = $this->getCheckout()->getQuote();
        if (!$quote->getItemsCount()) {
            throw new Jmango360_Japi_Exception(Mage::helper('japi')->__('Cart is empty.'), Jmango360_Japi_Model_Request::HTTP_INTERNAL_ERROR);
        }

        /*
         * Three possible methods:
        *  @Mage_Checkout_Model_Type_Onepage::METHOD_GUEST
        *  @Mage_Checkout_Model_Type_Onepage::METHOD_REGISTER
        *  @Mage_Checkout_Model_Type_Onepage::METHOD_CUSTOMER
        *
        *  Checks the method, set the checkout method to the quote and saves it to the sales_flat_quote
        */
        $checkoutMethod = $this->_getRequest()->getParam('checkout_method', null);
        if (empty($checkoutMethod)) {
            $checkoutMethod = $this->getCheckoutMethod();
        }
        $this->saveCheckoutMethod($checkoutMethod);

        $requestCheckoutMethod = $this->_getRequest()->getParam('checkout_method');
        if ($requestCheckoutMethod != $checkoutMethod) {
            throw new Jmango360_Japi_Exception(Mage::helper('japi')->__("Checkout method is not synchronised. Requested method is %s and detected method is %s.", $requestCheckoutMethod, $checkoutMethod), Jmango360_Japi_Model_Request::HTTP_INTERNAL_ERROR);
        }

        switch ($checkoutMethod) {
            case Mage_Checkout_Model_Type_Onepage::METHOD_GUEST:
                /* @var $model Jmango360_Japi_Model_Rest_Checkout_Onepage */
                $model = Mage::getModel('japi/rest_checkout_onepage');
                $data = $model->checkout();
                break;
            case Mage_Checkout_Model_Type_Onepage::METHOD_CUSTOMER:
                /* @var $model Jmango360_Japi_Model_Rest_Checkout_Onepage */
                $model = Mage::getModel('japi/rest_checkout_onepage');
                $data = $model->checkout();
                break;
            case Mage_Checkout_Model_Type_Onepage::METHOD_REGISTER:
                /* @var $model Jmango360_Japi_Model_Rest_Checkout_Onepage */
                $model = Mage::getModel('japi/rest_checkout_onepage');
                $data = $model->checkout();
                break;
            default:
                throw new Jmango360_Japi_Exception(
                    Mage::helper('japi')->__('Checkout method not implemented yet: ' . $checkoutMethod),
                    Jmango360_Japi_Model_Request::HTTP_INTERNAL_ERROR
                );
                break;
        }

        return $data;
    }

    protected function _updateShippingMethod()
    {
        /* @var $model Jmango360_Japi_Model_Rest_Checkout_Onepage */
        $model = Mage::getModel('japi/rest_checkout_onepage');
        $data = $model->updateShippingMethod();

        return $data;
    }

    protected function _updatePaymentMethod()
    {
        /* @var $model Jmango360_Japi_Model_Rest_Checkout_Onepage */
        $model = Mage::getModel('japi/rest_checkout_onepage');
        $data = $model->updatePaymentMethod();

        return $data;
    }

    protected function _updateOrder()
    {
        /* @var $model Jmango360_Japi_Model_Rest_Checkout_Onepage */
        $model = Mage::getModel('japi/rest_checkout_onepage');
        $data = $model->updateOrder();

        return $data;
    }

    protected function _getOnePage()
    {
        return Mage::getSingleton('checkout/type_onepage');
    }

    protected function _getCheckoutMethods()
    {
        $model = Mage::getModel('japi/rest_checkout_methods');
        $data = $model->getCheckoutMethods();

        return $data;
    }

    /**
     * @return Mage_Checkout_Model_Session
     */
    protected function _getSession()
    {
        return Mage::getSingleton('checkout/session');
    }

    /**
     * @return Jmango360_Japi_Model_Request
     */
    protected function _getRequest()
    {
        return Mage::helper('japi')->getRequest();
    }

    /**
     * @return Jmango360_Japi_Model_Response
     */
    protected function _getResponse()
    {
        return Mage::helper('japi')->getResponse();
    }

    /**
     * @return Jmango360_Japi_Model_Server
     */
    protected function _getServer()
    {
        return Mage::helper('japi')->getServer();
    }
}