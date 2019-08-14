<?php

class Jmango360_Japi_Model_Rest_Cart_Update extends Jmango360_Japi_Model_Rest_Cart
{
    const XML_PATH_SHIPPING_ORIGIN_COUNTRY = 'shipping/origin/country_id';

    /**
     * @throws Jmango360_Japi_Exception
     * @return array
     */
    public function addCartItem()
    {
        $params = $this->_getRequest()->getParams();
        $isOfflineCart = Mage::getSingleton('core/session')->getIsOffilneCart();

        // Clean params
        unset($params['SID']);
        unset($params['token']);
        unset($params['quote_id']);
        unset($params['version']);

        if (isset($params['qty'])) {
            $filter = new Zend_Filter_LocalizedToNormalized(
                array('locale' => Mage::app()->getLocale()->getLocaleCode())
            );
            $params['qty'] = $filter->filter($params['qty']);
        }

        $product = $this->_initProduct();
        if (!$product || $product->getStatus() != Mage_Catalog_Model_Product_Status::STATUS_ENABLED) {
            throw new Jmango360_Japi_Exception(Mage::helper('japi')->__('Product not found.'), Jmango360_Japi_Model_Request::HTTP_INTERNAL_ERROR);
        }

        if ($isOfflineCart) {
            /**
             * Check minimum & maximum quantity allowed for sale
             */
            if (($product->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_SIMPLE || $product->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_BUNDLE) && $product->getStockItem()) { //Check for Simple and Bundle products
                $_minSaleQty = $product->getStockItem()->getMinSaleQty() ? $product->getStockItem()->getMinSaleQty() : 0;
                if ($params['qty'] < $_minSaleQty) {
                    throw new Jmango360_Japi_Exception(Mage::helper('cataloginventory')->__('%s: Minimum quantity allowed for purchase is %s.', $product->getName(), $_minSaleQty * 1), Jmango360_Japi_Model_Request::HTTP_INTERNAL_ERROR);
                }

                $_maxSaleQty = $product->getStockItem()->getMaxSaleQty();
                if ($_maxSaleQty && $params['qty'] > $_maxSaleQty) {
                    throw new Jmango360_Japi_Exception(Mage::helper('cataloginventory')->__('%s: Maximum quantity allowed for purchase is %s.', $product->getName(), $_maxSaleQty * 1), Jmango360_Japi_Model_Request::HTTP_INTERNAL_ERROR);
                }
            } else if ($product->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE) { //Check for Configurable products
                if (isset($params['super_attribute'])) {
                    $childProduct = Mage::getModel('catalog/product_type_configurable')->getProductByAttributes($params['super_attribute'], $product);
                    if ($childProduct->getId() && $childProduct->getStockItem()) {
                        $_minSaleQty = $childProduct->getStockItem()->getMinSaleQty() ? $childProduct->getStockItem()->getMinSaleQty() : 0;
                        if ($params['qty'] < $_minSaleQty) {
                            throw new Jmango360_Japi_Exception(Mage::helper('cataloginventory')->__('%s: Minimum quantity allowed for purchase is %s.', $product->getName(), $_minSaleQty * 1), Jmango360_Japi_Model_Request::HTTP_INTERNAL_ERROR);
                        }

                        $_maxSaleQty = $childProduct->getStockItem()->getMaxSaleQty();
                        if ($_maxSaleQty && $params['qty'] > $_maxSaleQty) {
                            throw new Jmango360_Japi_Exception(Mage::helper('cataloginventory')->__('%s: Maximum quantity allowed for purchase is %s.', $product->getName(), $_maxSaleQty * 1), Jmango360_Japi_Model_Request::HTTP_INTERNAL_ERROR);
                        }
                    }
                }
            } else if ($product->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_GROUPED) { //Check for Group products
                if (isset($params['super_group'])) {
                    $message = '';
                    $_superGroups = $params['super_group'];
                    foreach ($_superGroups as $key => $val) {
                        /* @var $_simpleProduct Mage_Catalog_Model_Product */
                        $_simpleProduct = Mage::getModel('catalog/product')->load((int)$key);
                        if ($_simpleProduct->getId() && $_simpleProduct->getStockItem()) {
                            $_minSaleQty = $_simpleProduct->getStockItem()->getMinSaleQty() ? $_simpleProduct->getStockItem()->getMinSaleQty() : 0;
                            if ($val < $_minSaleQty) {
                                $message .= Mage::helper('cataloginventory')->__('%s: Minimum quantity allowed for purchase is %s.', $_simpleProduct->getName(), $_minSaleQty * 1);
                                $message .= "\n";
                            }

                            $_maxSaleQty = $_simpleProduct->getStockItem()->getMaxSaleQty();
                            if ($_maxSaleQty && $val > $_maxSaleQty) {
                                $message .= Mage::helper('cataloginventory')->__('%s: Maximum quantity allowed for purchase is %s.', $_simpleProduct->getName(), $_maxSaleQty * 1);
                                $message .= "\n";
                            }
                        }
                    }

                    if ($message != '') {
                        throw new Jmango360_Japi_Exception($message, Jmango360_Japi_Model_Request::HTTP_INTERNAL_ERROR);
                    }
                }
            }
        }

        $related = explode(',', $this->_getRequest()->getParam('related_products'));

        /**
         * Flag as JMango360 order
         * Removed from 3.4.0 as MPLUGIN-1966
         */
        //$this->getQuote()->setData('japi', 1);

        /**
         * TODO: Workaround for some module tring save shipping address before quote saved
         */
        if (Mage::helper('core')->isModuleEnabled('RapidCommerce_Defaultdestination')) {
            $this->getQuote()->save();
        }

        /**
         * MPLUGIN-1852: Fix Kega_AutoShipping
         */
        if (Mage::helper('core')->isModuleEnabled('Kega_AutoShipping')) {
            /* @var $customer Mage_Customer_Model_Session */
            $customer = Mage::getSingleton('customer/session');
            if ($customer->isLoggedIn()) {
                /* @var $shippingAddress Mage_Sales_Model_Quote_Address */
                $shippingAddress = Mage::getSingleton('checkout/session')->getQuote()->getShippingAddress();
                $country = $shippingAddress->getCountryId();
                if (!$country) {
                    $shippingAddress->setCountryId(Mage::getStoreConfig(self::XML_PATH_SHIPPING_ORIGIN_COUNTRY));
                }
            }
        }

        $this->addProduct($product, $params);
        if (count($related)) {
            $this->addProductsByIds($related);
        }

        $this->save();

        $this->_getSession()->setCartWasUpdated(true);

        Mage::dispatchEvent('checkout_cart_add_product_complete',
            array('product' => $product, 'request' => Mage::app()->getRequest(), 'response' => Mage::app()->getResponse())
        );

        $data = $this->_getCart();
        $data['message'] = Mage::helper('japi')->__(
            '%s was added to your shopping cart.',
            Mage::helper('core')->escapeHtml($product->getName())
        );

        return $data;
    }

    public function updateCartItem()
    {
        $cartData = $this->_getRequest()->getParam('cart', null);

        if (is_array($cartData) && !empty($cartData)) {
            $filter = new Zend_Filter_LocalizedToNormalized(
                array('locale' => Mage::app()->getLocale()->getLocaleCode())
            );

            foreach ($cartData as $index => $data) {
                if (isset($data['qty'])) {
                    $cartData[$index]['qty'] = $filter->filter(trim($data['qty']));
                }
            }

            if (!$this->getCustomerSession()->getCustomer()->getId() && $this->getQuote()->getCustomerId()) {
                $this->getQuote()->setCustomerId(null);
            }

            $cartData = $this->suggestItemsQty($cartData);
            $this->updateItems($cartData)->save();
            $this->_resetQuote();
        } else {
            throw new Jmango360_Japi_Exception(Mage::helper('japi')->__('No cart data found.'), Jmango360_Japi_Model_Request::HTTP_INTERNAL_ERROR);
        }
        $this->_getSession()->setCartWasUpdated(true);

        $data = $this->_getCart();
        $data['message'] = Mage::helper('japi')->__('Your shopping cart has been updated.');

        return $data;
    }

    /**
     * @param $quoteItem
     * @param $params
     * @throws Jmango360_Japi_Exception
     */
    protected function _validateQtyUpdate($quoteItem, $params)
    {
        /** @var Mage_Catalog_Model_Product $product */
        $product = Mage::getModel('catalog/product')->load($quoteItem->getProductId());
        if (($product->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_SIMPLE || $product->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_BUNDLE) && $product->getStockItem()) { //Check for Simple and Bundle products
            $_minSaleQty = $product->getStockItem()->getMinSaleQty() ? $product->getStockItem()->getMinSaleQty() : 0;
            if ($params['qty'] < $_minSaleQty) {
                throw new Jmango360_Japi_Exception(Mage::helper('cataloginventory')->__('%s: Minimum quantity allowed for purchase is %s.', $product->getName(), $_minSaleQty * 1), Jmango360_Japi_Model_Request::HTTP_INTERNAL_ERROR);
            }
            $_maxSaleQty = $product->getStockItem()->getMaxSaleQty();
            if ($_maxSaleQty && $params['qty'] > $_maxSaleQty) {
                throw new Jmango360_Japi_Exception(Mage::helper('cataloginventory')->__('%s: Maximum quantity allowed for purchase is %s.', $product->getName(), $_maxSaleQty * 1), Jmango360_Japi_Model_Request::HTTP_INTERNAL_ERROR);
            }
        } else if ($product->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE) { //Check for Configurable products
            $buy_request = Mage::helper('japi/product')->getCartProductBuyRequest($quoteItem, $product);
            $childProduct = Mage::getModel('catalog/product_type_configurable')->getProductByAttributes($buy_request['super_attribute'], $product);

            if ($childProduct->getId() && $childProduct->getStockItem()) {
                $_minSaleQty = $childProduct->getStockItem()->getMinSaleQty() ? $childProduct->getStockItem()->getMinSaleQty() : 0;
                if ($params['qty'] < $_minSaleQty) {
                    throw new Jmango360_Japi_Exception(Mage::helper('cataloginventory')->__('%s: Minimum quantity allowed for purchase is %s.', $product->getName(), $_minSaleQty * 1), Jmango360_Japi_Model_Request::HTTP_INTERNAL_ERROR);
                }

                $_maxSaleQty = $childProduct->getStockItem()->getMaxSaleQty();
                if ($_maxSaleQty && $params['qty'] > $_maxSaleQty) {
                    throw new Jmango360_Japi_Exception(Mage::helper('cataloginventory')->__('%s: Maximum quantity allowed for purchase is %s.', $product->getName(), $_maxSaleQty * 1), Jmango360_Japi_Model_Request::HTTP_INTERNAL_ERROR);
                }
            }
        }
    }

    protected function _updateCartOptions($params)
    {
        if (!isset($params['id'])) {
            throw new Jmango360_Japi_Exception(Mage::helper('japi')->__('No bundle-product cart item ID found.'), Jmango360_Japi_Model_Request::HTTP_INTERNAL_ERROR);
        }

        $cart = $this->getQuote();
        $item = $cart->getItemById($params['id']);
        if (!is_object($item) || !$item->getProductId()) {
            throw new Jmango360_Japi_Exception(Mage::helper('japi')->__(' .'), Jmango360_Japi_Model_Request::HTTP_INTERNAL_ERROR);
        }

        $params['product_id'] = $item->getProductId();

        if (!isset($params['options'])) {
            $params['options'] = array();
        }

        $item = Mage::getSingleton('checkout/cart')->updateItem($params['id'], new Varien_Object($params));
        if (is_string($item)) {
            throw new Jmango360_Japi_Exception($item, Jmango360_Japi_Model_Request::HTTP_INTERNAL_ERROR);
        }
        if ($item->getHasError()) {
            throw new Jmango360_Japi_Exception($item->getMessage(), Jmango360_Japi_Model_Request::HTTP_INTERNAL_ERROR);
        }

        return $this;
    }

    public function deleteCartItem()
    {
        $id = (int)$this->_getRequest()->getParam('id', null);
        $messages = array();

        $check = $this->getQuote()->getItemById($id);
        if (!is_object($check) || !$check->getId()) {
            $message = Mage::helper('japi')->__('The product you are trying to delete could not be found in the cart (item id is %s not found in cart).', $id);
            //Mage::log($message, Zend_Log::WARN, $this->_logname);
            $messages[] = $message;
        } else {
            $this->removeItem($id)->save();
            $this->_resetQuote();
        }

        if (empty($messages)) {
            $messages[] = Mage::helper('japi')->__('The product has been deleted from your shopping cart.');
        }

        $data = $this->_getCart();
        $data['message'] = implode("/n", $messages);

        return $data;
    }

    public function emptyCart()
    {
        /* @var $cart Mage_Checkout_Model_Cart */
        $cart = Mage::getSingleton('checkout/cart');
        $cart->truncate()->save();
        /* @var $session Mage_Checkout_Model_Session */
        $session = Mage::getSingleton('checkout/session');
        $session->setCartWasUpdated(true);
        /* @var $quote Mage_Sales_Model_Quote */
        $quote = $session->getQuote();
        $quote->removePayment()->save();
        $quote->getShippingAddress()->setShippingMethod('')->save();

        $data = $this->_getCart();
        return $data;
    }

    public function updateCartItemOption()
    {
        /* @var $cart Mage_Checkout_Model_Cart */
        $cart = Mage::getSingleton('checkout/cart');
        $id = (int)$this->_getRequest()->getParam('id');
        $params = $this->_getRequest()->getParams();

        if (!isset($params['options'])) {
            $params['options'] = array();
        }

        try {
            $quoteItem = $cart->getQuote()->getItemById($id);
            if (!$quoteItem) {
                throw new Jmango360_Japi_Exception(
                    Mage::helper('japi')->__('Quote item is not found.'),
                    Jmango360_Japi_Model_Request::HTTP_INTERNAL_ERROR
                );
            }

            if (isset($params['qty'])) {
                $filter = new Zend_Filter_LocalizedToNormalized(
                    array('locale' => Mage::app()->getLocale()->getLocaleCode())
                );
                $params['qty'] = $filter->filter($params['qty']);
            } else {
                $params['qty'] = $quoteItem->getQty();
            }

            $item = $cart->updateItem($id, new Varien_Object($params));
            if (is_string($item)) {
                throw new Jmango360_Japi_Exception(
                    $item,
                    Jmango360_Japi_Model_Request::HTTP_INTERNAL_ERROR
                );
            }
            if ($item->getHasError()) {
                throw new Jmango360_Japi_Exception(
                    $item->getMessage(),
                    Jmango360_Japi_Model_Request::HTTP_INTERNAL_ERROR
                );
            }

            $related = $this->_getRequest()->getParam('related_product');
            if (!empty($related)) {
                $cart->addProductsByIds(explode(',', $related));
            }

            $cart->save();
            $this->_getSession()->setCartWasUpdated(true);

            Mage::dispatchEvent('checkout_cart_update_item_complete',
                array('item' => $item, 'request' => $this->_getRequest(), 'response' => $this->_getResponse())
            );

            if (!$cart->getQuote()->getHasError()) {
                $data = $this->_getCart();
                $data['message'] = Mage::helper('japi')->__(
                    '%s was updated in your shopping cart.',
                    Mage::helper('core')->escapeHtml($item->getProduct()->getName())
                );

                return $data;
            }
        } catch (Mage_Core_Exception $e) {
            throw new Jmango360_Japi_Exception(
                join("\n", array_unique(explode("\n", $e->getMessage()))),
                Jmango360_Japi_Model_Request::HTTP_INTERNAL_ERROR
            );
        } catch (Exception $e) {
            throw new Jmango360_Japi_Exception(
                $e->getMessage(),
                Jmango360_Japi_Model_Request::HTTP_INTERNAL_ERROR
            );
        }

        return null;
    }
}
