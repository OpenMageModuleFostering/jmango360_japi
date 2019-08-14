<?php

class Jmango360_Japi_Model_Rest_Customer_Order_List extends Mage_Customer_Model_Customer
{

    protected $countJapiOders = 0;

    public function getOrderList()
    {
        $limit = $this->_getRequest()->getParam('limit');
        $page = $this->_getRequest()->getParam('p');
        $data['orders'] = $this->_getOrderList(null, null, false, $limit, $page);

        return $data;
    }

    public function getOrderDetails()
    {
        $request = $this->_getRequest();
        $incrementId = $request->getParam('increment_id', null);
        $orderId = $request->getParam('order_id', null);

        if (!empty($orderId)) {
            $data = $this->_getOrderById($orderId);
        } elseif (!empty($incrementId)) {
            $data = $this->_getOrderByRealId($incrementId);
        } else {
            throw new Jmango360_Japi_Exception(
                Mage::helper('japi')->__('Order not found (no ID).'),
                Jmango360_Japi_Model_Request::HTTP_INTERNAL_ERROR
            );
        }

        return array('order' => count($data) ? $data[0] : new stdClass());
    }

    public function getJapiOrders()
    {
        $limit = $this->_getRequest()->getParam('limit', 20);
        $page = $this->_getRequest()->getParam('p', 1);
        $date = $this->_getRequest()->getParam('date');
        $quoteIds = $this->_getRequest()->getParam('quote_ids');
        $data['orders'] = $this->_getJapiOrderList($limit, $page, true, $date, $quoteIds);
        $data['total_orders'] = $this->countJapiOders;

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

    protected function _getOrderList($orderId = null, $incrementId = null, $showDetails = false, $limit = null, $page = null)
    {
        $data = array();

        /* @var $orderCollection Mage_Sales_Model_Resource_Order_Collection */
        $orderCollection = Mage::getModel('sales/order')->getCollection()
            ->addFieldToFilter('state', array('in' => Mage::getSingleton('sales/order_config')->getVisibleOnFrontStates()))
            ->setOrder('created_at', 'desc');

        if ($limit && $page) {
            $orderCollection
                ->setPageSize($limit)
                ->setCurPage($page);
        }

        $customerId = $this->_getSession()->getCustomerId();
        if (!empty($customerId)) {
            $orderCollection->addFieldToFilter('customer_id', $customerId);
        }

        if (!empty($orderId)) {
            $orderCollection->addFieldToFilter('entity_id', $orderId);
        }

        if (!empty($incrementId)) {
            $orderCollection->addFieldToFilter('increment_id', $incrementId);
        }

        foreach ($orderCollection as $order) {
            /* @var $order Mage_Sales_Model_Order */
            if ($showDetails) {
                $orderData = $this->_getOrderDetails($order);
            } else {
                $orderData = array(
                    'entity_id' => (int)$order->getData('entity_id'),
                    'increment_id' => $order->getData('increment_id'),
                    'created_at' => $order->getData('created_at'),
                    'grand_total' => (float)$order->getData('grand_total'),
                    'formatted_grand_total' => $order->getOrderCurrency()->formatPrecision($order->getData('grand_total'), 2, array(), false, false)
                );
            }

            $orderData['status'] = $order->getStatusLabel();

            $data[] = $orderData;
        }

        return $data;
    }

    /**
     * Get Japi Orders list
     *
     * @param null $limit
     * @param null $page
     * @param bool $showDetails
     * @param null $date
     * @param null $quoteIds
     * @return array
     */
    protected function _getJapiOrderList($limit = null, $page = null, $showDetails = false, $date = null, $quoteIds = null)
    {
        $data = array();

        /* @var $orderCollection Mage_Sales_Model_Resource_Order_Collection */
        $orderCollection = Mage::getModel('sales/order')->getCollection()
            ->addFieldToFilter('japi', '1')
            ->addFieldToFilter('state', array('in' => Mage::getSingleton('sales/order_config')->getVisibleOnFrontStates()))
            ->setOrder('created_at', 'desc');

        if ($limit && $page) {
            $orderCollection
                ->setPageSize($limit)
                ->setCurPage($page);
        }

        if (!empty($date)) {
            $date = date('Y-m-d H:i:s', strtotime($date));
            $orderCollection->addAttributeToFilter('created_at', array('to' => $date));
        }

        if (!empty($quoteIds)) {
            $quoteIds = explode(',', $quoteIds);
            if (count($quoteIds)) {
                $orderCollection->addAttributeToFilter('quote_id', array('in' => $quoteIds));
            }
        }

        foreach ($orderCollection as $order) {
            /* @var $order Mage_Sales_Model_Order */
            if ($showDetails) {
                $orderData = $this->_getOrderDetails($order);
            } else {
                $orderData = array(
                    'entity_id' => (int)$order->getData('entity_id'),
                    'increment_id' => $order->getData('increment_id'),
                    'created_at' => $order->getData('created_at'),
                    'quote_id' => $order->getQuoteId(),
                    'grand_total' => (float)$order->getData('grand_total'),
                    'formatted_grand_total' => $order->getOrderCurrency()->formatPrecision($order->getData('grand_total'), 2, array(), false, false)
                );
            }

            $orderData['status'] = $order->getStatusLabel();

            $this->countJapiOders = $orderCollection->getSize();

            $data[] = $orderData;
        }

        return $data;
    }

    public function _getOrderDetails(Mage_Sales_Model_Order $order)
    {
        Mage::register('current_order', $order, true);

        if ($order->getGiftMessageId() > 0) {
            $order->setGiftMessage(Mage::getSingleton('giftmessage/message')->load($order->getGiftMessageId())->getMessage());
        }

        $data = $order->toArray();

        $data['shipping_address'] = $order->getShippingAddress()->toArray();
        $data['billing_address'] = $order->getBillingAddress()->toArray();
        $data['shipping_title'] = $this->_getOrderShippingMethod($order);
        $data['payment_title'] = $this->_getOrderPaymentMethod($order);

        try {
            $data = $this->_getOrderItemsAndTotals($data, $order);
        } catch (Exception $e) {
            Mage::logException($e);
            $data['totals'] = array();
        }

        return $data;
    }

    protected function _getOrderItemsAndTotals(array $data, Mage_Sales_Model_Order $order)
    {
        /* @var $helper Jmango360_Japi_Helper_Data */
        $helper = Mage::helper('japi');
        /* @var $layout Mage_Core_Model_Layout */
        $layout = $helper->loadLayout('sales_order_view');
        if (!$layout) return $data;

        foreach ($layout->getAllBlocks() as $block) {
            /* @var $block Mage_Core_Model_Template */
            if ($block->getType() == 'sales/order_totals') {
                $totalsBlock = $block;
            }
            if ($block->getType() == 'sales/order_items') {
                $itemsBlock = $block;
            }
        }

        if (!empty($itemsBlock)) {
            /* @var $itemsBlock Mage_Sales_Block_Order_Items */
            $itemsCollection = $order->getItemsCollection();
            $items = array();

            foreach ($itemsCollection as $itemModel) {
                /* @var $itemModel Mage_Sales_Model_Order_Item */
                if ($itemModel->getParentItem()) continue;

                $item = $itemModel->toArray();
                /* @var $block Mage_Sales_Block_Order_Item_Renderer_Default */
                $block = $itemsBlock->getItemRenderer($itemModel->getProductType())->setItem($itemModel);
                if ($itemModel->getProductType() == 'bundle') {
                    $options = array();
                    $childenItems = $itemModel->getChildrenItems();
                    foreach ($childenItems as $childenItem) {
                        $attributes = $this->_getSelectionAttributes($childenItem);
                        if (empty($attributes)) continue;

                        if (!isset($options[$attributes['option_id']])) {
                            $options[$attributes['option_id']] = array(
                                'label' => $helper->escapeHtml($attributes['option_label']),
                                'value' => $this->_getSelectionHtml($childenItem, $attributes, $order)
                            );
                        } else {
                            $options[$attributes['option_id']]['value'] .= "\n" . $this->_getSelectionHtml($childenItem, $attributes, $order);
                        }
                    }
                    $item['options'] = array_values($options);
                } else {
                    $item['options'] = $block->getItemOptions();
                }

                $items[] = $item;
            }

            $data['items'] = $items;
        }

        if (!empty($totalsBlock)) {
            /* @var $totalsBlock Mage_Sales_Block_Order_Totals */
            $totalsBlock->setOrder($order);
            $totalsBlock->toHtml();

            $totals = array();
            foreach ($totalsBlock->getTotals() as $total) {
                /* @var $total Varien_Object */
                $blockName = $total->getData('block_name');

                if ($blockName == 'tax') {
                    $totals = array_merge($totals, $this->_getTaxTotals($order));
                } elseif ($blockName) {
                    $html = $totalsBlock->getChildHtml($blockName);
                    $totals = array_merge($totals, $this->_getHtmlTotals($html));
                } else {
                    if (isset($total['value'])) {
                        $total->setData('formatted_value', $order->getOrderCurrency()->formatPrecision($total['value'], 2, array(), false));
                    }
                    $totals[] = $total->toArray();
                }
            }

            $data['totals'] = $totals;
        }

        return $data;
    }

    protected function _getSelectionHtml($childenItem, $attributes, $order)
    {
        return sprintf('%d x %s (%s)',
            $attributes['qty'],
            $childenItem->getName(),
            $order->getOrderCurrency()->formatPrecision($attributes['price'], 2, array(), false)
        );
    }

    protected function _getSelectionAttributes($item)
    {
        if ($item instanceof Mage_Sales_Model_Order_Item) {
            $options = $item->getProductOptions();
        } else {
            $options = $item->getOrderItem()->getProductOptions();
        }
        if (isset($options['bundle_selection_attributes'])) {
            return unserialize($options['bundle_selection_attributes']);
        }
        return null;
    }

    protected function _getHtmlTotals($html)
    {
        if (!$html) return array();

        $dom = new DOMDocument();
        $dom->loadHTML($html);
        $xpath = new DOMXPath($dom);
        $rows = $xpath->query('//tr');
        $totals = array();

        foreach ($rows as $index => $row) {
            $total = array('code' => 'total_' . $index);
            $columns = $xpath->query('descendant::td', $row);
            foreach ($columns as $i => $column) {
                if ($i == 0) {
                    $total['label'] = trim($column->nodeValue);
                } else {
                    $total['value'] = (float)preg_replace('/[^0-9.,]/', '', trim($column->nodeValue));
                    $total['formatted_value'] = trim($column->nodeValue);
                }
            }

            $totals[] = $total;
        }

        return $totals;
    }

    protected function _getTaxTotals(Mage_Sales_Model_Order $order)
    {
        /* @var $taxConfig Mage_Tax_Model_Config */
        $taxConfig = Mage::getSingleton('tax/config');
        $totals = array();

        if ($taxConfig->displaySalesFullSummary($order->getStore())) {
            $fullInfo = $order->getFullTaxInfo();
            if (!is_array($fullInfo)) return array();

            foreach ($fullInfo as $info) {
                if (isset($info['hidden']) && $info['hidden']) continue;

                $rates = $info['rates'];
                foreach ($rates as $rate) {
                    $total = array();
                    $total['code'] = $rate['code'];
                    $total['label'] = Mage::helper('japi')->escapeHtml($rate['title']);
                    if (!is_null($rate['percent'])) {
                        $total['label'] .= sprintf(' (%s%%)', (float)$rate['percent']);
                    }
                    $total['value'] = (float)$info['amount'];
                    $total['formatted_value'] = $order->getOrderCurrency()->formatPrecision($info['amount'], 2, array(), false);

                    $totals[] = $total;
                }
            }

            $weees = Mage::helper('tax')->getAllWeee($order);
            if (!is_array($weees)) return $totals;

            $weeIndex = 0;
            foreach ($weees as $weeeTitle => $weeeAmount) {
                $totals[] = array(
                    'code' => 'wee_' . $weeIndex++,
                    'label' => Mage::helper('japi')->escapeHtml($weeeTitle),
                    'value' => (float)$weeeAmount,
                    'formatted_value' => $order->getOrderCurrency()->formatPrecision($weeeAmount, 2, array(), false)
                );
            }
        }

        $totals[] = array(
            'code' => 'tax',
            'label' => Mage::helper('tax')->__('Tax'),
            'value' => (float)$order->getTaxAmount(),
            'formatted_value' => $order->getOrderCurrency()->formatPrecision($order->getTaxAmount(), 2, array(), false)
        );

        return $totals;
    }

    protected function _getOrderShippingMethod(Mage_Sales_Model_Order $order)
    {
        $shippingMethod = $order->getData('shipping_description');
        if (!$shippingMethod) return '';

        $shippingMethod = str_replace('<br><br>', ', ', $shippingMethod);
        $shippingMethod = str_replace('<br>', ', ', $shippingMethod);
        $shippingMethod = strip_tags($shippingMethod);
        $shippingMethod = trim($shippingMethod, ",\t\n ");

        return $shippingMethod;
    }

    protected function _getOrderPaymentMethod(Mage_Sales_Model_Order $order)
    {
        if ($order->getPayment()) {
            try {
                return $order->getPayment()->getMethodInstance()->getTitle();
            } catch (Exception $e) {
                Mage::logException($e);
            }
        }

        return '';
    }

    /**
     * @return Mage_Customer_Model_Session
     */
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