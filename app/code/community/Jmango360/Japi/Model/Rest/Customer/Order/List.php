<?php

class Jmango360_Japi_Model_Rest_Customer_Order_List extends Mage_Customer_Model_Customer
{

    protected $countJapiOders = 0;

    public function getOrderList()
    {
        $limit = $this->_getRequest()->getParam('limit');
        $page = $this->_getRequest()->getParam('p');
        $data = $this->_getOrderList(null, null, false, $limit, $page);

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

        return array('order' => !empty($data['orders']) ? $data['orders'][0] : null);
    }

    public function getJapiOrders()
    {
        $limit = (int)$this->_getRequest()->getParam('limit', 20);
        $page = (int)$this->_getRequest()->getParam('p', 1);
        $page = $page <= 1 ? 1 : $page;
        $date = $this->_getRequest()->getParam('date');
        $quoteIds = $this->_getRequest()->getParam('quote_ids');
        $fields = $this->_getRequest()->getParam('fields');
        $fields = explode(',', $fields);
        $data['orders'] = $this->_getJapiOrderList($limit, $page, !count($fields), $date, $quoteIds, $fields);
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
                    'formatted_grand_total' => $order->getOrderCurrency()->formatPrecision($order->getData('grand_total'), 2, array(), false, false),
                    'order_currency_code' => $order->getData('order_currency_code')
                );
            }

            $orderData['status'] = $order->getStatusLabel();

            $data['orders'][] = $orderData;
        }

        $data['total_orders'] = $orderCollection->getSize();

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
     * @param array $fields
     * @return array
     */
    protected function _getJapiOrderList($limit = null, $page = null, $showDetails = false, $date = null, $quoteIds = null, $fields = array())
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
                $orderData = $this->_getOrderDetails($order, true);
            } else {
                $orderData = array(
                    'entity_id' => (int)$order->getData('entity_id'),
                    'increment_id' => $order->getData('increment_id'),
                    'store_id' => $order->getData('store_id'),
                    'created_at' => $order->getData('created_at'),
                    'quote_id' => $order->getQuoteId(),
                    'grand_total' => (float)$order->getData('grand_total'),
                    'formatted_grand_total' => $order->getOrderCurrency()->formatPrecision($order->getData('grand_total'), 2, array(), false, false)
                );
                foreach ($fields as $field) {
                    $orderData[$field] = $order->getData($field);
                }
            }

            $orderData['status'] = $order->getStatusLabel();

            $this->countJapiOders = $orderCollection->getSize();

            $data[] = $orderData;
        }

        return $data;
    }

    public function _getOrderDetails(Mage_Sales_Model_Order $order, $isJapi = false)
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
            if ($isJapi) {
                $data = $this->_getJapiOrderItemsAndTotals($data, $order);
            } else {
                $data = $this->_getOrderItemsAndTotals($data, $order);
            }
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

                /**
                 * MPLUGIN-1730: Add weee tax calculation
                 */
                if ($helper->isModuleEnabled('Mage_Weee')) {
                    /* @var Mage_Weee_Helper_Data $weeeHelper */
                    $weeeHelper = Mage::helper('weee');

                    if ($weeeHelper->typeOfDisplay($itemModel, array(0, 1, 4), 'sales')) {
                        $item['price'] += $itemModel->getWeeeTaxAppliedAmount() + $itemModel->getWeeeTaxDisposition();
                        $item['row_total'] += $itemModel->getWeeeTaxAppliedRowAmount() + $itemModel->getWeeeTaxRowDisposition();
                    }
                }

                $product = $this->_getProductFromOrderItem($itemModel);
                if ($product && $product->getId() && $product->getData('status') == 1) {
                    $item['image'] = Mage::helper('japi/product')->getProductImage($product);
                    if ($product->getData('visibility') != '' && $product->getData('visibility') != 1) {
                        $item['product_url'] = $product->getUrlInStore();
                    } else {
                        $item['product_url'] = null;
                    }
                } else {
                    $item['image'] = null;
                    $item['product_url'] = null;
                }

                $options = array();

                if ($itemModel->getProductType() == 'bundle') {
                    $childenItems = $itemModel->getChildrenItems();
                    foreach ($childenItems as $childenItem) {
                        $attributes = $this->_getSelectionAttributes($childenItem);
                        if (empty($attributes)) continue;

                        if (!isset($options[$attributes['option_id']])) {
                            $options[$attributes['option_id']] = array(
                                'label' => $helper->escapeHtml($attributes['option_label']),
                                'value' => $this->_getSelectionHtml($childenItem, $attributes, $order),
                                'price' => '' . $attributes['price'],
                                'qty' => '' . $attributes['qty'],
                                'type' => 'drop_down'
                            );
                        } else {
                            $options[$attributes['option_id']]['value'] .= '||' . $this->_getSelectionHtml($childenItem, $attributes, $order);
                            $options[$attributes['option_id']]['price'] .= '||' . $attributes['price'];
                            $options[$attributes['option_id']]['qty'] .= '||' . $attributes['qty'];
                            $options[$attributes['option_id']]['type'] = 'multiple';
                        }
                    }
                }

                /* @var $block Mage_Sales_Block_Order_Item_Renderer_Default */
                $block = $itemsBlock->getItemRenderer($itemModel->getProductType())->setItem($itemModel);
                $customOptions = $block->getItemOptions();
                if ($customOptions) {
                    $options += $this->_getProductCustomOptions($customOptions, $product);
                }

                $item['options'] = array_values($options);

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
                    $totals = array_merge($totals, $this->_getHtmlTotals($html, $order));
                } else {
                    if (isset($total['value'])) {
                        if (is_numeric($total['value'])) {
                            $total->setData('currency_symbol', $totalsBlock->getOrder()->getOrderCurrencyCode());
                            $total->setData('formatted_value', $order->getOrderCurrency()->formatPrecision($total['value'], 2, array(), false));
                        } else {
                            $total->setData('currency_symbol', $totalsBlock->getOrder()->getBaseCurrencyCode());
                            $total->setData('formatted_value', strip_tags($total['value']));
                            if ($total['code'] == 'base_grandtotal') {
                                $total->setData('value', $totalsBlock->getOrder()->getBaseGrandTotal());
                            }
                        }
                    }
                    $totals[] = $total->toArray();
                }
            }

            $data['totals'] = $totals;
        }

        return $data;
    }

    protected function _getJapiOrderItemsAndTotals(array $data, Mage_Sales_Model_Order $order)
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

                $items[$itemModel->getId()] = $itemModel->toArray();
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
                    $items[$itemModel->getId()]['options'] = array_values($options);
                } else {
                    $items[$itemModel->getId()]['options'] = $block->getItemOptions();
                }
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
                    $totals = array_merge($totals, $this->_getHtmlTotals($html, $order));
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

    /**
     * Return related product
     *
     * @param Mage_Sales_Model_Order_Item $item
     * @return Mage_Catalog_Model_Product|null
     */
    protected function _getProductFromOrderItem($item)
    {
        if (!$item || !$item->getId() || !$item->getProductId()) return null;

        $productOptions = $item->getProductOptions();
        if ($item->getProductType() == 'simple') {
            if (!empty($productOptions['info_buyRequest']['super_attribute'])) {
                $productId = $productOptions['info_buyRequest']['product_id'];
                if (!$productId) $productId = $productOptions['info_buyRequest']['product'];
            } else {
                $productId = $item->getProductId();
            }
        } else {
            $productId = $item->getProductId();
        }

        if (!is_numeric($productId)) return null;

        $product = Mage::getModel('catalog/product')->setStoreId($item->getStoreId())->load($productId, array(
            'status', 'visibility', 'image', 'small_image', 'thumbnail'
        ));

        return $product;
    }

    /**
     * Process product custom options data
     */
    protected function _getProductCustomOptions($options, $product)
    {
        if (!is_array($options)) return null;

        foreach ($options as $k => $option) {
            if (!isset($option['option_type'])) {
                continue;
            }

            $options[$k]['type'] = $option['option_type'];
            $options[$k]['value'] = $option['print_value'];
            switch ($option['option_type']) {
                case 'checkbox':
                case 'multiple':
                    if ($product && $product->getId()) {
                        foreach ($product->getOptions() as $productOption) {
                            if ($option['option_id'] == $productOption->getId()) {
                                $newValues = array();
                                $oldValues = explode(',', $option['option_value']);
                                foreach ($productOption->getValues() as $value) {
                                    if (in_array($value->getId(), $oldValues)) {
                                        $newValues[] = $value->getTitle();
                                    }
                                }
                                if (count($newValues) == count($oldValues)) {
                                    $options[$k]['value'] = implode('||', $newValues);
                                }
                                break;
                            }
                        }
                    }
                    break;
            }
        }

        return $options;
    }

    protected function _getSelectionHtml($childenItem, $attributes, $order)
    {
        return $childenItem->getName();
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

    protected function _getHtmlTotals($html, $order = null)
    {
        if (!$html) return array();

        $dom = new DOMDocument();
        $dom->loadHTML($html);
        $xpath = new DOMXPath($dom);
        $rows = $xpath->query('//tr');
        $totals = array();

        foreach ($rows as $index => $row) {
            $total = array('code' => 'total_' . $index);
            if ($order) $total['currency_symbol'] = $order->getOrderCurrencyCode();
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
                    $total['currency_symbol'] = $order->getOrderCurrencyCode();
                    $total['formatted_value'] = $order->getOrderCurrency()->formatPrecision($info['amount'], 2, array(), false);

                    $totals[] = $total;
                }
            }

            /* @var $taxHelper Mage_Tax_Helper_Data */
            $taxHelper = Mage::helper('tax');
            if (method_exists($taxHelper, 'getAllWeee')) {
                $weees = $taxHelper->getAllWeee($order);
            } else {
                $weees = array();
            }
            if (is_array($weees)) {
                $weeIndex = 0;
                foreach ($weees as $weeeTitle => $weeeAmount) {
                    $totals[] = array(
                        'code' => 'wee_' . $weeIndex++,
                        'label' => Mage::helper('japi')->escapeHtml($weeeTitle),
                        'value' => (float)$weeeAmount,
                        'currency_symbol' => $order->getOrderCurrencyCode(),
                        'formatted_value' => $order->getOrderCurrency()->formatPrecision($weeeAmount, 2, array(), false)
                    );
                }
            }
        }

        $totals[] = array(
            'code' => 'tax',
            'label' => Mage::helper('tax')->__('Tax'),
            'value' => (float)$order->getTaxAmount(),
            'currency_symbol' => $order->getOrderCurrencyCode(),
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
                $paymentTitle = $order->getPayment()->getMethodInstance()->getTitle();
                if ($paymentTitle) return Mage::helper('core')->__($paymentTitle);

                $paymentInfoBlock = Mage::helper('payment')->getInfoBlock($order->getPayment());
                $html = $paymentInfoBlock->toHtml();
                $html = str_replace("\n", "", trim(strip_tags($html)));

                return $html;
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