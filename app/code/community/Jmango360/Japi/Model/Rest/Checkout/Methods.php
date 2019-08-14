<?php

/**
 * TODO: Create a field mapper that maps custom made shipping and payment method fields to the response of getCheckoutMethods
 * @author Administrator
 *
 */
class Jmango360_Japi_Model_Rest_Checkout_Methods extends Jmango360_Japi_Model_Rest_Checkout
{
    protected static $_carriers;

    protected $_errors = array();

    protected $_shippingMethodsDisplayed = null;
    protected $_paymentMethodsDisplayed = null;

    /**
     * The method shows the basic information in the App for shipping
     *   all other parameters are used in /<module>/shipping to count the shipping price
     *   the final shipping price is responce of the collect totals call
     * The "free shipping subtotal" can be displayed in the "specificerrmsg" if applicable.
     * "Specific countries" is in the standard configuration but not used in standard Magento code.
     * The show method (show not applicable shipping methods) is in configuration, however not used in the standard code.
     * @TODO: create admin field mapper
     */
    private $_shippingMethodFields = array(
        /*
         * Is the method active. If not active not in response.
         */
        'active',
        /*
         * Display title
         */
        'title',
        /*
         * Display name
         */
        'name',
        /*
         * Price for shipping
         */
        'price',
        /*
         * Message to show. Could be used as explanation of the price calculation in the App.
         * The Magento standard text (not available...) is not used.
         */
        'specificerrmsg',
        /*
         * The handling type and fee is not added. Is mainly used in packages and weight quanties.
         * 	Seldom used bij simple shops and not technical shop owners.
         * The handling fee still can be used, only the accounting is not shown in the App.
         * The explanation of accounting the handling fee could be added in the specificerrmsg
         */
        //'handling_type',
        //'handling_fee',
        /*
         * Show the method even when it is not active
         * use the specificerrmsg to explain why not active and why shown
         */
        'showmethod',
        /*
         * Order in which the methods are shown
         */
        'sort_order',
        /*
         * Only used in specific countries
         * This is not in the response.
         * countries are filtered out from the response if applicable
         */
        //'sallowspecific',
        //'specific_country',
    );

    /**
     * The method shows the basic information in the App for billing
     *   all other parameters are used in /<module>/shipping to count the shipping price
     *   the final shipping price is response of the collect totals call
     * @TODO: create admin field mapper
     */
    private $_paymentMethodFields = array(
        /*
         * Is the method active. If not active not in response.
         */
        'active',
        /*
         * Display title
         */
        'title',
        /*
         * Message to show. Could be used as explanation of the price calculation in the App.
         * The Magento standard text (not available...) is not used.
         */
        'instructions',
        /*
         * Order in which the methods are shown
         */
        'sort_order',
        /*
         * Only used in specific countries
         * This is not in the response.
         * countries are filtered out from the response if applicable
         */
        //'sallowspecific',
        //'specific_country',
    );

    public function getCheckoutMethods()
    {
        $data['shipping_methods'] = null;
        $data['payment_methods'] = null;

        if ($this->_validateMethods()) {
            $data['shipping_methods'] = $this->_getShippingMethods();
            $data['payment_methods'] = $this->_getPaymentMethods();
        }

        $data['methods_info'] = $this->getErrors();

        return $data;
    }

    public function getShippingMethods()
    {
        $shippingMethods = null;
        if ($this->_validateMethods()) {
            $shippingMethods = $this->_getShippingMethods();
        }

        return $shippingMethods;
    }

    public function getPaymentMethods()
    {
        $paymentMethods = null;
        if ($this->_validateMethods()) {
            $paymentMethods = $this->_getPaymentMethods();
        }

        return $paymentMethods;
    }

    public function getValidatePaymentMethods($paymentMethod = null)
    {
        $paymentMethods = null;
        if ($this->_validateMethods()) {
            $paymentMethods = $this->_getValidatePaymentMethods($paymentMethod);
        }

        return $paymentMethods;
    }

    /*
     * The getCheckoutMethods can give some of the methods applicable before address and cart is saved. But is prevented to do this by the validate below.
     * -- To get All applicable and VALID methods and there price shipping address and cart have to be saved to quote tables
     * -- The counts below prevent showing the incomplete methods
     * -- Removing the count will use a temporary (fake) shipping request simulating some shipping address and item parameters
     * -- This can be used in some custom made solutions, but by default it is prevented by the counts belwo
     */
    private function _validateMethods()
    {
        $quote = $this->getQuote();
        $valid = true;
        $shippingAddress = $quote->getShippingAddress();

        if (!is_object($shippingAddress) || !$shippingAddress->getLastname() || !$shippingAddress->getStreet() || !$shippingAddress->getCountryId()) {
            $this->_errors[] = Mage::helper('japi')->__('Methods are not complete yet. Cart shipping address is not yet completed and saved.');
            $valid = false;
        }

        if (!count($quote->getAllVisibleItems())) {
            $this->_errors[] = Mage::helper('japi')->__('Methods are not complete yet. Cart is empty.');
            $valid = false;
        }

        return $valid;
    }

    public function getErrors()
    {
        return $this->_errors;
    }

    /*
     * Dilemma of showing all active shipping methods in the App before all information is collected
     *
     * The shipping method in the address table has to be a combination of the carrier and the carrier method
     * In the configuration only the carrier code is given. The method is collected from the "collectRates" method
     * from the carriers shipping model
     *
     * The shipping methods in magento filosofy can only be populated only if shipping address is known,
     * because there price is shown next to the shipping method.
     * Some of the prices can only be accounted if address (location), product items price, weight etc are known
     * All information is available after the address and products are given
     * The collect totals done before the App shows the product total prices in the checkout will show the right price
     * Below is a work around by collectRates with a temporary request object (request object = shipping information for collect rates in collect totals)
     *
     * @TODO: look for a better "Magentic way"
     */
    protected function _getShippingMethods()
    {
        $options = array();
        /* @var $taxHelper Mage_Tax_Helper_Data */
        $taxHelper = Mage::helper('tax');
        /* @var $block Mage_Checkout_Block_Onepage_Shipping_Method_Available */
        $block = Mage::helper('japi')->getBlock('checkout/onepage_shipping_method_available');
        $_shippingRateGroups = $block->getShippingRates();
        if (count($_shippingRateGroups)) {
            foreach ($_shippingRateGroups as $code => $_rates) {
                foreach ($_rates as $_rate) {
                    /* @var $_rate Mage_Sales_Model_Quote_Address_Rate */
                    if ($_rate->getErrorMessage()) {
                        $this->_errors[] = $_rate->getErrorMessage();
                    } else {
                        $method = $_rate->getmethod();
                        $carrier = $_rate->getCarrier();
                        if (empty($method)) {
                            $method = $carrier;
                        }
                        $shippingMethod = $_rate->getCode();

                        $options[$shippingMethod]['shipping_method'] = $shippingMethod;
                        $options[$shippingMethod]['carrier'] = $carrier;
                        $options[$shippingMethod]['carrier_title'] = $_rate->getCarrierTitle();
                        $options[$shippingMethod]['method'] = $method;
                        $options[$shippingMethod]['method_title'] = $_rate->getMethodTitle();
                        $options[$shippingMethod]['description'] = $_rate->getMethodDescription();
                        $options[$shippingMethod]['shipping_cost'] = $taxHelper->getShippingPrice(
                            $_rate->getPrice(),
                            $taxHelper->displayShippingPriceIncludingTax(),
                            $block->getAddress()
                        );

                        $_excl = strip_tags($block->getShippingPrice(
                            $_rate->getPrice(), $taxHelper->displayShippingPriceIncludingTax()
                        ));
                        $options[$shippingMethod]['display_price_excl'] = $_excl;

                        $_incl = strip_tags($block->getShippingPrice($_rate->getPrice(), true));
                        if ($taxHelper->displayShippingBothPrices() && $_incl != $_excl) {
                            $options[$shippingMethod]['display_price_incl'] = strip_tags($block->getShippingPrice(
                                $_rate->getPrice(), $taxHelper->displayShippingPriceIncludingTax()
                            ));
                        }

                        foreach ($this->_shippingMethodFields as $fieldName) {
                            if (empty($options[$shippingMethod][$fieldName])) {
                                $options[$shippingMethod][$fieldName] = Mage::getStoreConfig("carriers/$code/$fieldName");
                            }
                        }

                        if (empty($options[$shippingMethod]['title'])) {
                            $options[$shippingMethod]['title'] = trim(implode(' ', array($_rate->getCarrierTitle(), $_rate->getMethodTitle())));
                        }

                        // MPLUGIN-816
                        $options[$shippingMethod]['active'] = $options[$shippingMethod]['active'] ? '1' : '0';
                    }
                }
            }
        } else {
            /*
             * In case you would like to catch shipping methods in an early stage, before having added the address or the first cart item.
             * -- the method below simulates a shipping methods/rates request with an empty cart items array and some key values set to 1
             * -- this will display most of the shipping methods with the correct shipping code
             * -- it can not however count the correct prices
             */
            $this->_setShippingMethodsDisplayed($this->_getDisplayedShippingMethods());
            Mage::dispatchEvent('rest_checkout_shipping_methods', array('object' => $this));

            foreach ($this->_getShippingMethodsDisplayed() as $code => $carrierContainer) {
                $rates = $this->_getRates($code, $carrierContainer);
                if ($rates) {
                    foreach ($rates as $rate) {
                        /* @var $rate Mage_Sales_Model_Quote_Address_Rate */
                        $method = $rate->getmethod();
                        $carrier = $rate->getCarrier();
                        if (empty($method)) {
                            $method = $carrier;
                        }
                        $shippingMethod = $carrier . '_' . $method;

                        $options[$shippingMethod]['shipping_method'] = $shippingMethod;
                        $options[$shippingMethod]['carrier'] = $rate->getCarrier();
                        $options[$shippingMethod]['carrier_title'] = $rate->getCarrierTitle();
                        $options[$shippingMethod]['method'] = $rate->getMethod();
                        $options[$shippingMethod]['method_title'] = $rate->getMethodTitle();
                        foreach ($this->_shippingMethodFields as $fieldName) {
                            if (empty($options[$shippingMethod][$fieldName]) && $fieldName != 'price') {
                                $options[$shippingMethod][$fieldName] = Mage::getStoreConfig("carriers/$code/$fieldName");
                            }
                        }
                        if (empty($options[$shippingMethod]['title'])) {
                            $options[$shippingMethod]['title'] = trim(implode(' ', array($rate->getCarrierTitle(), $rate->getMethodTitle())));
                        }
                    }
                } else {
                    $message = Mage::helper('japi')->__('Shipping method has no shipping carrier object: %s', $code);
                    $this->_errors[] = $message;
                    //mage::log($message, Zend_Log::WARN, $this->_logname);
                    //throw new Jmango360_Japi_Exception($message, Jmango360_Japi_Model_Request::HTTP_INTERNAL_ERROR);
                }
            }
        }

        $excludedShippingMethods = explode(',', Mage::getStoreConfig('japi/jmango_rest_checkout_settings/shipping_excluded'));
        foreach ($options as $code => $option) {
            if (in_array($code, $excludedShippingMethods)) {
                unset($options[$code]);
            }
        }

        return $options;
    }

    /*
     * Used in work around for _getShippingmethods
     */
    protected function _getRates($code, $carrierContainer)
    {
        if (!method_exists($carrierContainer, 'collectRates')) {
            $message = Mage::helper('japi')->__('Carrier object has no collectRates method: %s', $code);
            $this->_errors[] = $message;
            //mage::log($message, Zend_Log::WARN, $this->_logname);
            //throw new Jmango360_Japi_Exception($message, Jmango360_Japi_Model_Request::HTTP_INTERNAL_ERROR);
            return false;
        }

        /*
         * Should be a populated collectRates request
         * -- in this early stage the request info not available
         */
        $request = $this->_getTempRequest();

        try {
            $carrierRates = $carrierContainer->collectRates($request);
        } catch (Exception $e) {
            $carrierRates = null;
            $message = $e->getMessage();
            $this->_errors[] = $message;
            //mage::log($e->getMessage(), Zend_Log::WARN, $this->_logname);
        }

        if (!is_object($carrierRates)) {
            $message = Mage::helper('japi')->__('Shipping method has no rates object: %s', $code);
            $this->_errors[] = $message;
            //mage::log($message, Zend_Log::WARN, $this->_logname);
            //throw new Jmango360_Japi_Exception($message, Jmango360_Japi_Model_Request::HTTP_INTERNAL_ERROR);
            return false;
        }

        if (!method_exists($carrierRates, 'getAllRates')) {
            $message = Mage::helper('japi')->__('Shipping method has no getAllRates function: %s', $code);
            $this->_errors[] = $message;
            //mage::log($message, Zend_Log::WARN, $this->_logname);
            //throw new Jmango360_Japi_Exception($message, Jmango360_Japi_Model_Request::HTTP_INTERNAL_ERROR);
            return false;
        }

        $rates = $carrierRates->getAllRates();
        if (!count($rates)) {
            $message = Mage::helper('japi')->__('Shipping method has no rates: %s', $code);
            $this->_errors[] = $message;
            //mage::log($message, Zend_Log::WARN, $this->_logname);
            //throw new Jmango360_Japi_Exception($message, Jmango360_Japi_Model_Request::HTTP_INTERNAL_ERROR);
            return false;
        }

        return $rates;
    }

    /*
     * Used in work around for _getShippingmethods
     */
    protected function _getTempRequest()
    {
        /* @var $request Mage_Shipping_Model_Rate_Request */
        $request = Mage::getModel('shipping/rate_request');
        $request->setDestCountryId(Mage::getStoreConfig('general/country/default'));
        $request->setPackageValue(1);
        $request->setPackageValueWithDiscount(1);
        $request->setPackageWeight(1);
        $request->setPackageQty(1);
        $request->setPackagePhysicalValue(1);
        $request->setStoreId(Mage::app()->getStore()->getId());
        $request->setWebsiteId(Mage::app()->getStore()->getWebsiteId());
        $request->setFreeShipping(0);
        $request->setBaseCurrency(Mage::app()->getStore()->getBaseCurrency());
        $request->setPackageCurrency(Mage::app()->getStore()->getCurrentCurrency());
        $request->setBaseSubtotalInclTax(1);
        $request->setFreeShipping(true);
        $request->setAllItems(array());

        return $request;
    }

    protected function _getValidatePaymentMethods($paymentMethod = null)
    {
        $quote = $this->getQuote();
        $store = $quote ? $quote->getStoreId() : null;
        $options = array();
        /* @var $block Mage_Checkout_Block_Onepage_Payment_Methods */
        $block = Mage::helper('japi')->getBlock('checkout/onepage_payment_methods', NULL, array('quote' => $quote, 'store' => $store));

        $paymentMethods = $block->getMethods();

        $total = $quote->getBaseSubtotal() + $quote->getShippingAddress()->getBaseShippingAmount();
        foreach ($paymentMethods as $method) {
            /* @var $method Mage_Payment_Model_Method_Abstract */
            if ($this->_canUsePaymentMethod($method) && ($total != 0 || $method->getCode() == 'free' || ($quote->hasRecurringItems() && $method->canManageRecurringProfiles()))) {
                $code = $method->getCode();
                $options[$code] = $method->toArray();

                if ($paymentMethod == $method->getCode()) {
                    //get payment form html
                    $formBlockName = $method->getFormBlockType();
                    if (!empty($formBlockName)) {
                        // Because of ICEPAY issues, we need create new blocks, not reuse them
                        /* @var $formBlock Mage_Payment_Block_Form */
                        $formBlock = Mage::app()->getLayout()->createBlock($formBlockName);
                        $formBlock->setData('method', $method);
                        $formBlock->setParentBlock($block);

                        $html = $formBlock->toHtml();
                        if (!empty($html)) {
                            $options[$code]['form'] = $this->_parseHtmlPaymentForm($html);
                        }
                    }
                }
            }
        }

        $excludedPaymentMethods = explode(',', Mage::getStoreConfig('japi/jmango_rest_checkout_settings/payment_excluded'));
        foreach ($options as $code => $option) {
            if (in_array($code, $excludedPaymentMethods)) {
                unset($options[$code]);
            }
        }

        return $options;
    }

    protected function _getPaymentMethods()
    {
        $quote = $this->getQuote();
        $store = $quote ? $quote->getStoreId() : null;
        $options = array();
        /* @var $block Mage_Checkout_Block_Onepage_Payment_Methods */
        $block = Mage::helper('japi')->getBlock('checkout/onepage_payment_methods', NULL, array('quote' => $quote, 'store' => $store));

        $paymentMethods = $block->getMethods();

        $total = $quote->getBaseSubtotal() + $quote->getShippingAddress()->getBaseShippingAmount();
        foreach ($paymentMethods as $method) {
            /* @var $method Mage_Payment_Model_Method_Abstract */
            if ($this->_canUsePaymentMethod($method) && ($total != 0 || $method->getCode() == 'free' || ($quote->hasRecurringItems() && $method->canManageRecurringProfiles()))) {

                $this->_assignPaymentMethod($method);
                $code = $method->getCode();
                $options[$code] = $method->toArray();
                $options[$code]['title'] = $this->_stripHtml($block->getMethodTitle($method));
                if (is_object($method->getInfoInstance())) {
                    $options[$code]['payment'] = $method->getInfoInstance()->toArray();
                }

                /*
                 * The payment forms contain the information to select from in frontend payment page
                 */
                $formBlockName = $method->getFormBlockType();
                if (!empty($formBlockName)) {
                    // Because of ICEPAY issues, we need create new blocks, not reuse them
                    /* @var $formBlock Mage_Payment_Block_Form */
                    $formBlock = Mage::app()->getLayout()->createBlock($formBlockName);
                    $formBlock->setData('method', $method);
                    $formBlock->setParentBlock($block);

                    $html = $formBlock->toHtml();
                    if (!empty($html)) {
                        $options[$code]['form'] = $this->_parseHtmlPaymentForm($html);
                    }
                }

                unset($options[$code]['info_instance']);

                foreach ($this->_paymentMethodFields as $fieldName) {
                    if (empty($options[$code][$fieldName])) {
                        $value = Mage::getStoreConfig("payment/$code/$fieldName");
                        $options[$code][$fieldName] = $value;
                    }
                }

                if (empty($options[$code]['title'])) {
                    $options[$code]['title'] = $code;
                }

                // MPLUGIN-816
                $options[$code]['active'] = $options[$code]['active'] ? '1' : '0';
            }
        }

        $excludedPaymentMethods = explode(',', Mage::getStoreConfig('japi/jmango_rest_checkout_settings/payment_excluded'));
        foreach ($options as $code => $option) {
            if (in_array($code, $excludedPaymentMethods)) {
                unset($options[$code]);
            }
        }

        return $options;
    }

    protected function _stripHtml($html)
    {
        return trim(strip_tags(str_replace('&nbsp;', ' ', $html)), " \t\n\r\0\x0B-");
    }

    protected function _parseHtmlPaymentForm($html)
    {
        return Mage::helper('japi')->parseHtmlForm($html);
    }

    /**
     * Check payment method model
     *
     * @param Mage_Payment_Model_Method_Abstract $method
     * @return bool
     */
    protected function _canUsePaymentMethod($method)
    {
        if (!$method->canUseForCountry($this->getQuote()->getBillingAddress()->getCountry())) {
            return false;
        }

        if (!$method->canUseForCurrency($this->getQuote()->getStore()->getBaseCurrencyCode())) {
            return false;
        }

        /**
         * Checking for min/max order total for assigned payment method
         */
        $total = $this->getQuote()->getBaseGrandTotal();
        $minTotal = $method->getConfigData('min_order_total');
        $maxTotal = $method->getConfigData('max_order_total');

        if ((!empty($minTotal) && ($total < $minTotal)) || (!empty($maxTotal) && ($total > $maxTotal))) {
            return false;
        }
        return true;
    }

    /**
     * Check and prepare payment method model
     *
     * Redeclare this method in child classes for declaring method info instance
     *
     * @param Mage_Payment_Model_Method_Abstract $method
     * @return bool
     */
    protected function _assignPaymentMethod($method)
    {
        $method->setInfoInstance($this->getQuote()->getPayment());
        return $this;
    }

    protected function _getDisplayedShippingMethods()
    {
        $carriers = array();
        $config = Mage::getStoreConfig('carriers');
        foreach ($config as $code => $carrierConfig) {
            if (Mage::getStoreConfigFlag('carriers/' . $code . '/active') /*|| Mage::getStoreConfigFlag('carriers/'.$code.'/showmethod')*/) {
                $carrierModel = $this->_getCarrier($code, $carrierConfig);
                if ($carrierModel) {
                    $carriers[$code] = $carrierModel;
                }
            }
        }
        return $carriers;
    }

    public function _getDisplayedPaymentMethods()
    {
        $payment = array();
        $config = Mage::getStoreConfig('payment');
        foreach ($config as $code => $paymentConfig) {
            if (Mage::getStoreConfigFlag('payment/' . $code . '/active')) {
                $paymentModel = $this->_getCarrier($code, $paymentConfig);
                if ($paymentModel) {
                    $payment[$code] = $paymentModel;
                }
            }
        }
        return $payment;
    }

    protected function _getCarrier($code, $config, $store = null)
    {
        if (!isset($config['model'])) {
            return false;
        }
        $modelName = $config['model'];

        /**
         * Added protection from not existing models usage.
         * Related with module uninstall process
         */
        try {
            $carrier = Mage::getModel($modelName);
        } catch (Exception $e) {
            Mage::logException($e);
            return false;
        }
        $carrier->setId($code)->setStore($store);
        self::$_carriers[$code] = $carrier;
        return self::$_carriers[$code];
    }

    private function _setShippingMethodsDisplayed($methods)
    {
        $this->_shippingMethodsDisplayed = $methods;

        return $this;
    }

    protected function _getShippingMethodsDisplayed()
    {
        return $this->_shippingMethodsDisplayed;
    }

    private function _setPaymentMethodsDisplayed($methods)
    {
        $this->_paymentMethodsDisplayed = $methods;

        return $this;
    }

    protected function _getPaymentMethodsDisplayed()
    {
        return $this->_paymentMethodsDisplayed;
    }
}