<?php

class Jmango360_Japi_Model_Rest_Customer_Order extends Mage_Customer_Model_Customer
{
    public function getCustomerOrderList()
    {
        $data['orders'] = $this->_getOrderList();

        return $data;
    }

    public function getCustomerOrderDetails()
    {
        $request = $this->_getRequest();
        $incrementId = $request->getParam('increment_id', null);
        $orderId = $request->getParam('order_id', null);

        if (!empty($orderId)) {
            $data = $this->_getOrderById($orderId);
        } elseif (!empty($incrementId)) {
            $data = $this->_getOrderByRealId($incrementId);
        } else {
            throw new Jmango360_Japi_Exception(Mage::helper('japi')->__('Order not found (no ID).'), Jmango360_Japi_Model_Request::HTTP_INTERNAL_ERROR);
        }

        return $data;
    }

    protected function _getOrderById($orderId)
    {
        return $this->_getOrderList($orderId, null, true);
    }

    protected function _getOrderByRealId($incrementId)
    {
        return $this->_getOrderList(null, $incrementId, true);
    }

    /**
     * Retrieve list of orders.
     * Filtration could be applied
     *
     * @param $orderId
     * @param $incrementId
     * @param $addDetails
     * @return array
     */
    protected function _getOrderList($orderId = null, $incrementId = null, $addDetails = false)
    {
        $data = array();

        /* @var $orderCollection Mage_Sales_Model_Resource_Order_Collection */
        $orderCollection = Mage::getModel("sales/order")->getCollection()
            ->addFieldToFilter('state', array('in' => Mage::getSingleton('sales/order_config')->getVisibleOnFrontStates()))
            ->setOrder('created_at', 'desc');

        $customerId = Mage::getSingleton('customer/session')->getCustomerId();
        if (!empty($customerId)) {
            $orderCollection->addFieldToFilter('customer_id', $customerId);
        }
        if (!empty($orderId)) {
            $orderCollection->addFieldToFilter('entity_id', $orderId);
        }
        if (!empty($incrementId)) {
            $orderCollection->addFieldToFilter('increment_id', $incrementId);
        }

        $orderCollection->addExpressionFieldToSelect(
            'customer_name',
            'concat(ifnull({{prefix}}, ""), ifnull({{firstname}}, ""), if({{middlename}} is null or {{middlename}} = "", " ", concat(" ",{{middlename}}," ")), ifnull({{lastname}},""), ifnull({{suffix}}, ""))',
            array('prefix' => 'customer_prefix', 'firstname' => 'customer_firstname', 'middlename' => 'customer_middlename', 'lastname' => 'customer_lastname', 'suffix' => 'customer_suffix')
        );

        foreach ($orderCollection as $order) {
            /* @var $order Mage_Sales_Model_Order */
            if ($addDetails) {
                $orderData = $this->_getOrderDetails($order);
            } else {
                $orderData = $order->toArray();
            }

            $orderData['status'] = $order->getStatusLabel();
            $orderData['shipping_description'] = str_replace('<br><br>', ', ', $orderData['shipping_description']);
            $orderData['shipping_description'] = str_replace('<br>', ', ', $orderData['shipping_description']);
            $orderData['shipping_description'] = strip_tags($orderData['shipping_description']);
            $orderData['shipping_description'] = trim($orderData['shipping_description'], ",\t\n ");

            foreach ($order->getAllItems() as $item) {
                /* @var $item Mage_Sales_Model_Order_Item */
                if (!$item->getParentItemId()) {
                    $orderData['products'][$item->getProductId()]['name'] = $item->getName();
                    $orderData['products'][$item->getProductId()]['qty'] = $item->getQtyOrdered();
                    $orderData['products'][$item->getProductId()]['price'] = $item->getPrice();
                    $orderData['products'][$item->getProductId()]['price_incl_tax'] = $item->getPriceInclTax();
                }
            }

            if ($order->getPayment()) {
                try {
                    $orderData['payment']['method'] = $order->getPayment()->getMethodInstance()->getTitle();
                } catch (Exception $e) {
                    $orderData['payment']['method'] = '';
                    Mage::logException($e);
                }
            }

            $data['orders'][] = $orderData;
        }

        return $data;
    }

    /**
     * Retrieve full order information
     *
     * @param Mage_Sales_Model_Order $order
     * @return array
     */
    public function _getOrderDetails($order)
    {
        if ($order->getGiftMessageId() > 0) {
            $order->setGiftMessage(Mage::getSingleton('giftmessage/message')->load($order->getGiftMessageId())
                ->getMessage());
        }

        $data = $order->toArray();

        $data['shipping_address'] = $order->getShippingAddress()->toArray();
        $data['billing_address'] = $order->getBillingAddress()->toArray();
        $data['items'] = array();

        foreach ($order->getAllItems() as $item) {
            /* @var $item Mage_Sales_Model_Order_Item */
            if ($item->getGiftMessageId() > 0) {
                $item->setGiftMessage(Mage::getSingleton('giftmessage/message')->load($item->getGiftMessageId())
                    ->getMessage());
            }

            $data['items'][$item->getId()] = $item->toArray();
            $product = Mage::getModel('catalog/product')->load($item->getProductId());
            $data['items'][$item->getId()]['image'] = Mage::helper('japi/product')->getProductImage($product);
            $data['items'][$item->getId()]['product'] = $product;
        }

        $data['payment'] = $order->getPayment()->toArray();

        $data['status_history'] = array();

        foreach ($order->getAllStatusHistory() as $history) {
            $data['status_history'][$history->getId()] = $history->toArray();
        }

        return $data;
    }

    protected function _getSession()
    {
        return Mage::getSingleton('customer/session');
    }

    protected function _getRequest()
    {
        return $this->_getServer()->getRequest();
    }

    protected function _getResponse()
    {
        return $this->_getServer()->getResponse();
    }

    /**
     * @return Jmango360_Japi_Model_Server
     */
    protected function _getServer()
    {
        return Mage::getSingleton('japi/server');
    }
}