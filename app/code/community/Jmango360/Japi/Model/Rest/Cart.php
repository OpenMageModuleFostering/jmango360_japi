<?php

class Jmango360_Japi_Model_Rest_Cart extends Mage_Checkout_Model_Cart
{
    protected $_logname = 'rest_cart.log';

    public function dispatch()
    {
        $action = $this->_getRequest()->getAction();
        $operation = $this->_getRequest()->getOperation();

        switch ($action . $operation) {
            case 'updateCart' . Jmango360_Japi_Model_Request::OPERATION_CREATE:
                $this->_checkQuote();
                $data = $this->_addCartItem();
                $this->_getResponse()->render($data);
                $this->_getResponse()->setHttpResponseCode(Jmango360_Japi_Model_Server::HTTP_CREATED);
                break;
            case 'updateCart' . Jmango360_Japi_Model_Request::OPERATION_UPDATE:
                $this->_checkQuote();
                $data = $this->_updateCartItem();
                $this->_getResponse()->render($data);
                $this->_getResponse()->setHttpResponseCode(Jmango360_Japi_Model_Server::HTTP_CREATED);
                break;
            case 'updateCart' . Jmango360_Japi_Model_Request::OPERATION_DELETE:
                $this->_checkQuote();
                $data = $this->_deleteCartItem();
                $this->_getResponse()->render($data);
                $this->_getResponse()->setHttpResponseCode(Jmango360_Japi_Model_Server::HTTP_CREATED);
                break;
            case 'updateCartItem' . Jmango360_Japi_Model_Request::OPERATION_UPDATE:
                $this->_checkQuote();
                $data = $this->_updateCartItemOption();
                $this->_getResponse()->render($data);
                $this->_getResponse()->setHttpResponseCode(Jmango360_Japi_Model_Server::HTTP_CREATED);
                break;
            case 'emptyCart' . Jmango360_Japi_Model_Request::OPERATION_DELETE:
                $data = $this->_emptyCart();
                $this->_getResponse()->render($data);
                $this->_getResponse()->setHttpResponseCode(Jmango360_Japi_Model_Server::HTTP_CREATED);
                break;
            case 'getCart' . Jmango360_Japi_Model_Request::OPERATION_RETRIEVE:
                $data = $this->_getCart();
                $this->_getResponse()->render($data);
                $this->_getResponse()->setHttpResponseCode(Jmango360_Japi_Model_Server::HTTP_CREATED);
                break;
            case 'updateCoupon' . Jmango360_Japi_Model_Request::OPERATION_CREATE:
                $data = $this->_addCoupon();
                $this->_getResponse()->render($data);
                $this->_getResponse()->setHttpResponseCode(Jmango360_Japi_Model_Server::HTTP_CREATED);
                break;
            case 'updateCoupon' . Jmango360_Japi_Model_Request::OPERATION_UPDATE:
                $data = $this->_updateCoupon();
                $this->_getResponse()->render($data);
                $this->_getResponse()->setHttpResponseCode(Jmango360_Japi_Model_Server::HTTP_CREATED);
                break;
            case 'updateCoupon' . Jmango360_Japi_Model_Request::OPERATION_DELETE:
                $data = $this->_deleteCoupon();
                $this->_getResponse()->render($data);
                $this->_getResponse()->setHttpResponseCode(Jmango360_Japi_Model_Server::HTTP_CREATED);
                break;
            default:
                throw new Jmango360_Japi_Exception(Mage::helper('japi')->__('Resource method not implemented'), Jmango360_Japi_Model_Request::HTTP_INTERNAL_ERROR);
                break;
        }
    }

    protected function _checkQuote()
    {
        $quoteId = $this->_getRequest()->getParam('quote_id', null);
        if (!$quoteId) return;

        $quote = Mage::getModel('sales/quote')->load($quoteId);
        if (!$quote->getId()) return;
        if (!$quote->getIsActive()) return;

        $session = $this->_getSession();
        if ($session->getQuoteId() == $quote->getId()) return;

        $session->replaceQuote($quote);
    }

    public function getCartData()
    {
        // if mobile version < 2.9.0, we should throw any error found
        $throwError = Mage::getSingleton('core/session')->getIsOffilneCart();

        $this->_validateQuote(!$throwError);
        $this->_validateMinimumAmount(!$throwError);
        $this->_validateGuestCanCheckout(!$throwError);

        $cart = $this->getQuote()->getData();

        /* @var $helper Jmango360_Japi_Helper_Product */
        $helper = Mage::helper('japi/product');
        /* @var $taxHelper Mage_Tax_Helper_Data */
        $taxHelper = Mage::helper('tax');

        $index = 0;
        $recollect = false;
        $cart['items'] = null;
        foreach ($this->getQuote()->getAllVisibleItems() as $item) {
            /* @var $item Mage_Sales_Model_Quote_Item */
            $product = $helper->convertProductIdToApiResponseV2($item->getProductId());

            // MPLUGIN-1259: Workaround to manual remove unavailable product
            if (!$product) {
                $this->removeItem($item->getId());
                $recollect = true;
                continue;
            }

            $cart['items'][$index] = $item->getData();

            if ($taxHelper->displayCartPriceInclTax() || $taxHelper->displayCartBothPrices()) {
                $cart['items'][$index]['price'] = $item->getData('price_incl_tax');
                $cart['items'][$index]['row_total'] = $item->getData('row_total_incl_tax');
            }

            $cart['items'][$index]['has_messages'] = $this->_getQuoteItemMessages($item);
            $cart['items'][$index]['product'] = array($product);
            $cart['items'][$index]['buy_request'] = $helper->getCartProductBuyRequest($item, $product);

            $index++;
        }

        if ($recollect) {
            $this->save();
            $this->_resetQuote();
            return false;
        }

        // Move here to prevent being cached in cart object
        $cart['items_count'] = $this->getSummaryQty();

        /**
         * MultiShipping is not supported yet. Always one shipping address is returned in the response
         */
        foreach ($this->getQuote()->getAllAddresses() as $address) {
            /* @var $address Mage_Sales_Model_Quote_Address */
            $cart['addresses'][$address->getAddressType()] = $address->getData();
        }

        $cart['totals'] = Mage::helper('japi')->getTotals();

        /**
         * Add the checkout methods
         * -- as long as the order is not submitted changing the carts address and items
         * -- can change the checkout methods
         */
        /* @var $methods Jmango360_Japi_Model_Rest_Checkout_Methods */
        $methods = Mage::getSingleton('japi/rest_checkout_methods');
        $shippingMethods = $methods->getShippingmethods();
        $cart['shipping_methods'] = empty($shippingMethods) ? new stdClass() : $shippingMethods;
        $paymentMethods = $methods->getPaymentMethods();
        $cart['payment_methods'] = empty($paymentMethods) ? new stdClass() : $paymentMethods;

        $cart['methods_info'] = $methods->getErrors();
        $cart['checkout_url'] = Mage::getUrl('japi/checkout/redirect', array(
            '_query' => array(
                'SID' => Mage::getSingleton('core/session')->getSessionId(),
                '___store' => Mage::app()->getStore()->getCode()
            ),
            '_secure' => true
        ));

        return $cart;
    }

    protected function _resetQuote()
    {
        $quoteId = $this->getQuote()->getId();
        $this->getCheckoutSession()->clear();
        $this->getCheckoutSession()->setQuoteId($quoteId);
        $this->unsetData('quote');
    }

    /**
     * @param Mage_Sales_Model_Quote_Item $quoteItem
     * @return array
     */
    protected function _getQuoteItemMessages($quoteItem)
    {
        $messages = array();

        // Add basic messages occuring during this page load
        $baseMessages = $quoteItem->getMessage(false);
        if ($baseMessages) {
            foreach ($baseMessages as $message) {
                $messages[] = array(
                    'message' => $message,
                    'type' => $quoteItem->getHasError() ? 2 : 1
                );
            }
        }

        // Add messages saved previously in checkout session
        $checkoutSession = $this->getCheckoutSession();
        if ($checkoutSession) {
            /* @var $collection Mage_Core_Model_Message_Collection */
            $collection = $checkoutSession->getQuoteItemMessages($quoteItem->getId(), true);
            if ($collection) {
                $additionalMessages = $collection->getItems();
                foreach ($additionalMessages as $message) {
                    /* @var $message Mage_Core_Model_Message_Abstract */
                    $messages[] = array(
                        'message' => $message->getCode(),
                        'type' => ($message->getType() == Mage_Core_Model_Message::ERROR) ? 2 : 1
                    );
                }
            }
        }

        return $messages;
    }

    protected function _addCartItem()
    {
        /* @var $model Jmango360_Japi_Model_Rest_Cart_Update */
        $model = Mage::getModel('japi/rest_cart_update');
        $data = $model->addCartItem();

        return $data;
    }

    protected function _updateCartItem()
    {
        /* @var $model Jmango360_Japi_Model_Rest_Cart_Update */
        $model = Mage::getModel('japi/rest_cart_update');
        $data = $model->updateCartItem();

        return $data;
    }

    protected function _updateCartItemOption()
    {
        /* @var $model Jmango360_Japi_Model_Rest_Cart_Update */
        $model = Mage::getModel('japi/rest_cart_update');
        $data = $model->updateCartItemOption();

        return $data;
    }

    protected function _deleteCartItem()
    {
        /* @var $model Jmango360_Japi_Model_Rest_Cart_Update */
        $model = Mage::getModel('japi/rest_cart_update');
        $data = $model->deleteCartItem();

        return $data;
    }

    protected function _emptyCart()
    {
        /* @var $model Jmango360_Japi_Model_Rest_Cart_Update */
        $model = Mage::getModel('japi/rest_cart_update');
        $data = $model->emptyCart();

        return $data;
    }

    protected function _getCart()
    {
        if (!$this->getQuote()->getId()) {
            $this->init()->save();
        }

        $throwError = Mage::getSingleton('core/session')->getIsOffilneCart();

        if ($error1 = $this->_validateQuote(!$throwError))
            $data['messages']['message'][] = array(
                'code' => Jmango360_Japi_Model_Request::HTTP_INTERNAL_ERROR,
                'message' => $error1,
                'type' => 1
            );

        if ($error2 = $this->_validateMinimumAmount(!$throwError))
            $data['messages']['message'][] = array(
                'code' => Jmango360_Japi_Model_Request::HTTP_INTERNAL_ERROR,
                'message' => $error2,
                'type' => 1
            );

        if ($error3 = $this->_validateGuestCanCheckout(!$throwError))
            $data['messages']['message'][] = array(
                'code' => Jmango360_Japi_Model_Request::HTTP_INTERNAL_ERROR,
                'message' => $error3,
                'type' => 1
            );

        $data['cart'] = $this->getCartData();
        if ($data['cart'] === false) {
            return $this->_getCart();
        }

        return $data;
    }

    protected function _initProduct()
    {
        /* @var $helper Mage_Core_Helper_Data */
        $helper = Mage::helper('core');

        $productId = (int)$this->_getRequest()->getParam('product_id');
        if (!$productId) return false;

        /* @var $product Mage_Catalog_Model_Product */
        $product = Mage::getModel('catalog/product')
            ->setStoreId(Mage::app()->getStore()->getId())
            ->load($productId);

        if (!$product->getId()) return false;

        /**
         * Support OrganicInternet_SimpleConfigurableProducts
         * which buy child product instead configurable product
         */
        if ($helper->isModuleEnabled('OrganicInternet_SimpleConfigurableProducts') && $product->getTypeId() == Mage_Catalog_Model_Product_Type_Configurable::TYPE_CODE) {
            $buyRequest = (array)$this->_getRequest()->getParam('super_attribute');
            $usedProducts = $product->getTypeInstance(true)->getUsedProducts(null, $product);
            $allowAttributes = $product->getTypeInstance(true)->getConfigurableAttributes($product);
            foreach ($usedProducts as $item) {
                $match = 0;
                foreach ($allowAttributes as $attribute) {
                    $productAttribute = $attribute->getProductAttribute();
                    $attributeId = $productAttribute->getId();
                    $attributeCode = $productAttribute->getAttributeCode();
                    if (array_key_exists($attributeId, $buyRequest) && $item->getData($attributeCode) == $buyRequest[$attributeId]) {
                        $match += 1;
                    }
                }
                if ($match == count($buyRequest)) {
                    $product = Mage::getModel('catalog/product')
                        ->setStoreId(Mage::app()->getStore()->getId())
                        ->load($item->getId());
                    break;
                }
            }
        }

        return $product;
    }

    protected function _addCoupon()
    {
        /* @var $model Jmango360_Japi_Model_Rest_Cart_Coupon */
        $model = Mage::getModel('japi/rest_cart_coupon');
        $data = $model->add();

        return $data;
    }

    protected function _updateCoupon()
    {
        /* @var $model Jmango360_Japi_Model_Rest_Cart_Coupon */
        $model = Mage::getModel('japi/rest_cart_coupon');
        $data = $model->update();

        return $data;
    }

    protected function _deleteCoupon()
    {
        /* @var $model Jmango360_Japi_Model_Rest_Cart_Coupon */
        $model = Mage::getModel('japi/rest_cart_coupon');
        $data = $model->remove();

        return $data;
    }

    protected function _validateQuote($return = false)
    {
        $quote = $this->getQuote()->collectTotals();
        if ($quote->getHasError()) {
            $messages = array();
            foreach ($quote->getMessages() as $message) {
                $messages[] = $message->getCode();
            }
            if ($return)
                return implode("\n", $messages);
            else {
                throw new Jmango360_Japi_Exception(
                    implode("\n", $messages),
                    Jmango360_Japi_Model_Request::HTTP_INTERNAL_ERROR
                );
            }
        }
    }

    public function getQuote()
    {
        $quote = parent::getQuote();
        $storeId = Mage::app()->getStore()->getId();
        if ($quote->getStoreId() != $storeId) {
            $quote->setStoreId($storeId);
        }

        return $quote;
    }

    protected function _validateMinimumAmount($return = false)
    {
        if ($this->getQuote()->getItemsCount() && !$this->getQuote()->validateMinimumAmount()) {
            $minimumAmount = Mage::app()->getLocale()->currency(Mage::app()->getStore()->getCurrentCurrencyCode())
                ->toCurrency(Mage::getStoreConfig('sales/minimum_order/amount'));

            $warning = Mage::getStoreConfig('sales/minimum_order/description')
                ? Mage::getStoreConfig('sales/minimum_order/description')
                : Mage::helper('checkout')->__('Minimum order amount is %s', $minimumAmount);

            if ($return) return $warning;
            else throw new Jmango360_Japi_Exception($warning, Jmango360_Japi_Model_Request::HTTP_INTERNAL_ERROR);
        }
    }

    protected function _validateGuestCanCheckout($return = false)
    {
        /* @var $helper Mage_Checkout_Helper_Data */
        $helper = Mage::helper('checkout');
        if (!$this->getQuote()->getCustomerId() && !$helper->isAllowedGuestCheckout($this->getQuote())) {
            $message = Mage::helper('japi')->__('Guest checkout is not enabled');

            if ($return) return $message;
            else throw new Jmango360_Japi_Exception($message, Jmango360_Japi_Model_Request::HTTP_INTERNAL_ERROR);
        }
    }

    public function getCouponData()
    {
        $data = $this->_getCart();

        $quote = $this->getQuote();
        $data['coupon'] = $quote->getCouponCode();

        return $data;
    }

    /**
     * @return Mage_Checkout_Model_Session
     */
    protected function _getSession()
    {
        return Mage::getSingleton('checkout/session');
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
