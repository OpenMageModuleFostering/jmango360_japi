<?php

class Jmango360_Japi_Model_Rest_Checkout_Submit extends Jmango360_Japi_Model_Rest_Checkout
{
    /**
     * If "makeCartInactiveAfterSubmit" is false;
     *  -- every new convertToOrder of the same cart will cancel the former order for this cart
     * If "makeCartInactiveAfterSubmit" is true
     *  -- the cart will be inactive after submit and the next submit wil warn the customer that the cart is empty
     */
    protected $_makeCartInactiveAfterSubmit = false;

    public function submitOrder()
    {
        /**
         * Populate data in session for Buckaroo
         */
        $POST = $this->_getSession()->getData('POST');
        if (is_array($POST)) {
            foreach ($POST as $key => $value) {
                if (!isset($_POST[$key])) {
                    $_POST[$key] = $value;
                }
            }
        }

        /**
         * Specific for payment forms some data from the ost from collect totals is used in the submit POST
         */
        $this->_callbackPaymentPostUsedInSubmit();

        /**
         * Import payment data again
         */
        $payment = $this->_getRequest()->getParam('payment');
        if (!empty($payment['method'])) {
            $this->getCheckout()->getQuote()->getPayment()->importData($payment);
        }

        /**
         * Security checks on quote
         */
        $quote = $this->getCheckout()->getQuote();

        /**
         * Security check parameters
         *   -- the (quote) ID can not be used tot collect the quote.
         *   -- if the quote is not already in the session something is wrong! Could be session is not set or wrong session.
         */
        $request = $this->_getRequest();
        $requestSubtotal = (float)$request->getParam('subtotal', null);
        $requestQuoteId = (int)$request->getParam('id', null);

        /**
         * Collect totals flag has to be set before convert quot to order
         *   -- after collect totals check grandtotal again
         */
        $quote->collectTotals()->save();
        $quoteSubtotal = (float)$this->_getTotal('subtotal');

        /**
         * Added (int) because in 1.7 the type differs; now it is checking the subtotal without the decimals, but that should be enough to avoid the risk having mixed up carts
         * Skip if place order form updatePaymentMethod API
         */
        if (!$this->_getSession()->getData('place_order')) {
            if ($requestSubtotal === "" || $requestQuoteId === "" || ((int)$requestSubtotal != (int)$quoteSubtotal) || ($requestQuoteId != $quote->getId())) {
                throw new Jmango360_Japi_Exception(
                    Mage::helper('japi')->__('Request info does not match the quote. Probably the cart is ordered or the session is expired.'),
                    Jmango360_Japi_Model_Request::HTTP_INTERNAL_ERROR
                );
            }
        }

        /**
         * Check if card stil active
         */
        if (!$quote->getIsActive()) {
            throw new Jmango360_Japi_Exception(Mage::helper('japi')->__('Cart is no longer active.'), Jmango360_Japi_Model_Request::HTTP_INTERNAL_ERROR);
        }

        /**
         * Validat the quote before being converted to order
         */
        $this->validateOrder();

        /**
         * Cancel order if order with this quote ID already exists
         */
        $makeCartInactiveAfterSubmit = $request->getParam('make_cart_inactive_after_submit', $this->_makeCartInactiveAfterSubmit);

        /**
         * Flag as JMango360 order
         */
        $quote->setData('japi', 1);

        /**
         * Convert the quote to order
         */
        $this->saveOrder();

        /**
         * Inactivate card
         *   -- This behaviour could be changed:
         *   -- -- inactivating the card creates a new cart the next time the cart is touched
         *   -- -- If anything goes wrong with the payment the cart is not automatically activated
         *   -- -- another way to deal with this is to cancel the existing order the next time the App calls the submitOrder function
         *   -- -- and automticaly create a new order.
         *   -- -- In order to do this uncomment the "this->cancelOrder" above before the this->saveOrder
         *   -- -- and switch off the setIsActive below.
         *   -- -- setIsActive has to be added to the success function after payment
         * Added a parameter so behaviour cab be inluenced by parameters in the call
         */
        if ($makeCartInactiveAfterSubmit) {
            $quote->setIsActive(false);
        }

        /**
         * Save quote to apply any changes
         */
        $quote->save();

        /**
         * Return payment data
         */
        $data = $this->_getPaymentData();

        return $data;
    }

    protected function _callbackPaymentPostUsedInSubmit()
    {
        $request = $this->_getRequest();
        $payment = array();

        $paymentPost = Mage::getSingleton('core/session')->getPaymentPostUsedInSubmit();
        if (empty($paymentPost) || !is_array($paymentPost)) {
            return $this;
        }

        foreach ($paymentPost as $key => $value) {
            $payment[$key] = $value;
        }

        $request->setPost('payment', $payment);

        return $this;
    }

    protected function _cancelFormerOrder()
    {
        $quote = $this->getCheckout()->getQuote();
        $orderId = $this->getCheckout()->getLastOrderId();
        if (!empty($orderId)) {
            $order = Mage::getModel('sales/order')->load($orderId);
            if ($quote->getId() && ($order->getQuoteId() == $quote->getId())) {
                $comment = Mage::helper('japi')->__('Replaced by new order.');
                $order->setState($order::STATE_CANCELED, true, $comment, false)->save();
            }
        }
    }

    /*
     * Multi shipping totals not supported in App
     */
    protected function _getTotal($name = null)
    {
        /* @var $taxConfig Mage_Tax_Model_Config */
        $taxConfig = Mage::getSingleton('tax/config');
        $quote = $this->getCheckout()->getQuote();
        $totals = $quote->getTotals();
        $data = null;

        foreach ($totals as $total) {
            /* @var $total Mage_Sales_Model_Quote_Address_Total_Abstract */
            if ($total->getCode() != $name) continue;

            switch ($name) {
                case 'subtotal':
                    if ($taxConfig->displayCartSubtotalBoth()) {
                        $data = $total->getValueExclTax();
                    } else {
                        $data = $total->getValue();
                    }
                    break;
            }
        }

        return $data;
    }

    /*
     * Multi shipping is not supported!
     */
    protected function _getPaymentData()
    {
        $checkout = $this->getCheckout();

        $data = array();
        if (!$checkout->getRedirectUrl()) {
            $data['online_payment'] = false;
            $data['payment_url'] = '';
        } else {
            $data['online_payment'] = true;

            /**
             * MPLUGIN-707: Fix for "https://www.vetcoolshops.nl/webshop/" with customer's module "Mage_Ideal"
             * MPLUGIN-828: Update - Always use Japi redirect for payment gate way Url
             */
            $_paymentUrl = $checkout->getRedirectUrl();
            $_paymentUrl = urldecode($_paymentUrl);
            $data['payment_url'] = Mage::getUrl('japi/rest_mage/redirect', array(
                '_query' => array('url' => $_paymentUrl),
                '_secure' => true
            ));
        }
        $data['last_order_id'] = $checkout->getLastOrderId();
        $data['last_real_order_id'] = $checkout->getLastRealOrderId();

        $data['session_id'] = Mage::getSingleton('core/session')->getSessionId();

        return $data;
    }

    /**
     * Fix for issue last increment id of store not updated
     */
    protected function _validateLastIncrementId()
    {
        /* @var $_coreResource Mage_Core_Model_Resource */
        $_coreResource = Mage::getSingleton('core/resource');
        $adapter = $_coreResource->getConnection('core_read');

        /* @var $eavEntityTypeResource Mage_Eav_Model_Resource_Entity_Type */
        $eavEntityTypeResource = Mage::getResourceModel('eav/entity_type');
        $eavEntityType = $adapter->select()
            ->from($eavEntityTypeResource->getMainTable())
            ->where('entity_type_code = ?', Mage_Sales_Model_Order::ENTITY)
            ->where('entity_model = ?', 'sales/order')
            ->limit(1);

        $dataEntityType = $adapter->fetchRow($eavEntityType);
        if ($dataEntityType) {
            $entityTypeId = (int)$dataEntityType['entity_type_id'];
            $lastIncrementStore = null;
            $lastOrderIncrement = null;
            if ($entityTypeId > 0) {
                /* @var $eavEntityStoreResource Mage_Eav_Model_Resource_Entity_Store */
                $eavEntityStoreResource = Mage::getResourceModel('eav/entity_store');
                $selectEntityStore = $adapter->select()
                    ->from($eavEntityStoreResource->getMainTable())
                    ->where('entity_type_id = ?', $entityTypeId)
                    ->where('store_id = ?', Mage::app()->getStore()->getStoreId())
                    ->limit(1);

                $dataEntityStore = $adapter->fetchRow($selectEntityStore);
                if ($dataEntityStore) {
                    $lastIncrementStore = $dataEntityStore['increment_last_id'];
                }

                /* @var $orderResource Mage_Sales_Model_Resource_Order */
                $orderResource = Mage::getResourceModel('sales/order');
                $selectOrder = $adapter->select()
                    ->from($orderResource->getMainTable())
                    ->where('store_id = ?', Mage::app()->getStore()->getStoreId())
                    ->order('created_at DESC')
                    ->order('increment_id DESC')
                    ->order($orderResource->getIdFieldName() . ' DESC')
                    ->limit(1);

                $dataOrder = $adapter->fetchRow($selectOrder);
                if ($dataOrder) {
                    $lastOrderIncrement = $dataOrder['increment_id'];
                }

                if ($lastIncrementStore != null && $lastOrderIncrement != null && ($lastIncrementStore != $lastOrderIncrement)) {
                    $write = $_coreResource->getConnection('core_write');
                    $data = array("increment_last_id" => $lastOrderIncrement);
                    $where = 'entity_type_id = ' . $entityTypeId;
                    $where .= ' AND store_id = ' . Mage::app()->getStore()->getStoreId();
                    $write->update($eavEntityStoreResource->getMainTable(), $data, $where);
                }
            }
        }
    }
}
