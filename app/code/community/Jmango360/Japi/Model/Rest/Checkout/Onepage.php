<?php

/**
 * TODO: Create a field mapper that maps custom made shipping and payment method fields to the response of getCheckoutMethods
 * @author Administrator
 *
 */
class Jmango360_Japi_Model_Rest_Checkout_Onepage extends Jmango360_Japi_Model_Rest_Checkout
{
    /**
     * Saved in the scope of the obeject duing vaildation of payment form data
     * -- used in saving and checking credit card
     * -- flat added to the request as issuer data
     */
    protected $_paymentFormInfo = null;

    /**
     * Credit card number is not saved in the database but checked in creditcard check
     */
    protected $_extraPaymentFields = array(
        'cc_number'
    );

    protected $_resetPaymentFieldnames = array(
        'cc_type',
        'cc_number_enc',
        'cc_last4',
        'cc_owner',
        'cc_exp_month',
        'cc_exp_year',
        'method_instance'
    );

    public function updateCartAddresses2()
    {
        $isLoggedIn = Mage::getSingleton('customer/session')->isLoggedIn();

        $billingData = $this->_getRequest()->getParam('billing_address', array());
        $customerBillingAddressId = $isLoggedIn ? $this->_getRequest()->getParam('billing_address_id', false) : false;

        if ($billingData || $customerBillingAddressId) {
            if (!isset($billingData['email'])) {
                $billingData['email'] = trim($this->_getRequest()->getParam('email'));
            }

            $billingResult = $this->saveBilling($billingData, $customerBillingAddressId);
            if (isset($billingResult['error'])) {
                throw new Jmango360_Japi_Exception($this->_convertMessage(@$billingResult['message']), Jmango360_Japi_Model_Request::HTTP_INTERNAL_ERROR);
            }
        }

        $shippingData = $this->_getRequest()->getParam('shipping_address', array());
        $customerShippingAddressId = $isLoggedIn ? $this->_getRequest()->getParam('shipping_address_id', false) : false;

        if ($shippingData || $customerShippingAddressId) {
            $shippingResult = $this->saveShipping($shippingData, $customerShippingAddressId);
            if (isset($shippingResult['error'])) {
                throw new Jmango360_Japi_Exception($this->_convertMessage(@$shippingResult['message']), Jmango360_Japi_Model_Request::HTTP_INTERNAL_ERROR);
            }
        }

        return $this->_getCheckoutData();
    }

    protected function _convertMessage($message)
    {
        if (!$message) return '';
        if (is_array($message)) return join("\n", $message);
        if (is_string($message)) return $message;
        return '';
    }

    public function updateCartAddresses()
    {
        /**
         * Creata a valid checkout for this session
         * Also check if customer is logged in
         *   - if so, assign customer
         */
        $this->initCheckout();

        /**
         * Now apply billing and shipping address as set by customer and requested by REST call
         */
        $quote = $this->getQuote();

        /**
         * Add billing address to quote
         */
        if ($billingAddress = $this->_getBillingAddress()) {
            $quote->setBillingAddress($billingAddress);
        }

        /**
         * Add shipping address to quote
         */
        if (!$quote->isVirtual()) {
            if ($shippingAddress = $this->_getShippingAddress()) {
                $quote->setShippingAddress($shippingAddress);
            }
        }

        /**
         * Collect totals
         */
        $quote->collectTotals()->save();
        if (!$quote->isVirtual()) {
            // Recollect Shipping rates for shipping methods
            $quote->getShippingAddress()->setCollectShippingRates(true);
        }

        /**
         * Get quote data
         */
        $data = $this->_getCheckoutData();

        return $data;
    }

    public function checkout()
    {
        /**
         * Saving the quote saves also quote related objects.
         * @var Mage_Sales_Model_Quote::_afterSave
         */
        $this->_saveAddresses();

        /**
         * Get payment forms from request params and validate all send payment data
         * -- translate form params to flat request params
         * -- save payment form params in database
         * -- save payment info in session for next step (submit)
         * In general trying to simulate the onestp checkout payment template and the data return
         * -- from the payment templates in the POST
         */
        $this->_preparePayment();

        /**
         * After setting shipping method in shipping address row, collect and set the totals and save
         */
        $this->getQuote()->setTotalsCollectedFlag(false);
        $this->getQuote()->collectTotals()->save();

        /**
         * Save payment POST in session to use in submit method
         */
        Mage::getSingleton('core/session')->setPaymentPostUsedInSubmit($this->_getRequest()->getParam('payment'));

        /**
         * Set response data
         */
        $data = $this->_getCheckoutData();

        /**
         * If the checkout was with register (new customer) method, the customer is logged in during checkout
         * -- the session id is refreshed after login
         */
        $data['session_id'] = Mage::getSingleton('core/session')->getSessionId();

        return $data;
    }

    public function updateShippingMethod()
    {
        $data = $this->_getRequest()->getParam('shipping_method', '');
        $result = $this->saveShippingMethod($data);

        if (!$result) {
            Mage::dispatchEvent(
                'checkout_controller_onepage_save_shipping_method',
                array(
                    'request' => $this->_getRequest(),
                    'quote' => $this->getQuote()
                )
            );

            /**
             * Set the quote basic customer info
             */
            $this->_setQuoteCustomerInfo();

            /**
             * Collect totals
             */
            $this->getQuote()->collectTotals()->save();

            /**
             * Return quote data
             */
            return $this->_getCheckoutData();
        } else {
            throw new Jmango360_Japi_Exception(
                $result['message'],
                Jmango360_Japi_Model_Request::HTTP_INTERNAL_ERROR
            );
        }
    }

    public function updatePaymentMethod()
    {
        /**
         * Save all POST data for Buckaroo using later
         */
        $POST = $this->_getRequest()->getParam('form', array());
        $POST['payment']['method'] = $this->_getRequest()->getParam('payment_method');
        $this->_getSession()->setData('POST', $POST);

        /**
         * Save payment
         */
        $this->_preparePayment();

        /**
         * Collect totals
         */
        $this->getQuote()->collectTotals()->save();

        /**
         * Return quote data
         */
        return $this->_getCheckoutData();
    }

    protected function _saveAddresses()
    {
        $request = $this->_getRequest();

        /**
         * If no carrier and method are known the quote can be saved but cannot collect all totals
         */
        $shippingMethod = $request->getParam('shipping_method', null);
        if (empty($shippingMethod)) {
            throw new Jmango360_Japi_Exception(Mage::helper('japi')->__('Shipping method can not be empty.'), Jmango360_Japi_Model_Request::HTTP_INTERNAL_ERROR);
        }

        /**
         * Check if shipping method exists and active
         */
        $availableMethods = Mage::getModel('japi/rest_checkout_methods')->getShippingMethods();
        if (empty($availableMethods)) {
            throw new Jmango360_Japi_Exception(Mage::helper('japi')->__('No valid shipping methods available yet.'), Jmango360_Japi_Model_Request::HTTP_INTERNAL_ERROR);
        }
        if (empty($availableMethods[$shippingMethod])) {
            throw new Jmango360_Japi_Exception(Mage::helper('japi')->__('This shipping method is currently not available.'), Jmango360_Japi_Model_Request::HTTP_INTERNAL_ERROR);
        }

        /**
         * Set the specific quote data
         */
        $quote = $this->getQuote();
        $quote->setShippingMethod($shippingMethod);

        /**
         * Virtual products ar not supported yet
         */
        $quote->setIsVirtual($request->getParam('is_virtual', false));

        /**
         * Virtual products ar not supported yet
         */
        $quote->setIsMultiShipping($request->getParam('is_multishipping', false));

        /**
         * Add billing address the the quote
         */
        $billingAddress = $this->_getBillingAddress();
        $quote->setBillingAddress($billingAddress);

        /**
         * Add shipping address to quote
         */
        if (!$quote->isVirtual()) {
            $shippingAddress = $this->_getShippingAddress();
            $shippingAddress->setShippingMethod($shippingMethod);
            $quote->setShippingAddress($shippingAddress);
        }

        /**
         * Set the quote basic customer info
         */
        $this->_setQuoteCustomerInfo();

        /**
         * Saving the quote and collecting the totals finished; send back all quote, price and totals information
         *  -- setStepData shipping complete is checked by saveBilling; if not set "setCollectShippingRates" is not set to true
         */
        $this->getCheckout()
            ->setStepData('shipping', 'complete', true)
            ->setStepData('shipping_method', 'allow', true);

        return $this;
    }

    /**
     * @return Mage_Sales_Model_Quote_Address
     * @throws Exception
     * @throws Jmango360_Japi_Exception
     */
    protected function _getShippingAddress()
    {
        $request = $this->_getRequest();
        $quote = $this->getQuote();
        /* @var $customer Mage_Customer_Model_Customer */
        $customer = Mage::getSingleton('customer/session')->getCustomer();

        /**
         * Used if "same_as_billing" is set to true in request
         */
        $billingAddress = $quote->getBillingAddress();

        /**
         * $requestBillingAddress below in the request is optional in collectTotals, ass address is saved before in updateCartAddress
         * -- this function (public getBillingAddress) is used by updateCartAddress to save the address in the http request!
         */
        $requestBillingAddress = $request->getParam('billing_address', array());
        $customerBillingAddress = $customer->getAddressById($request->getParam('billing_address_id'));

        $shippingAddress = $quote->getShippingAddress();

        /**
         * $address below in the request is optional in collectTotals, ass address is saved before in updateCartAddress
         * -- this function (public getBillingAddress) is used by updateCartAddress to save the address in the http request!
         */
        $address = $request->getParam('shipping_address', array());

        /**
         * Now we have a choice using: $shippingAddress (quote); $address (request); $customerShippingAddress (customer)
         * -- same-as-billing:
         * -- -- Priority to fill shipping address is (1) billing in request (2) billing address from quote (3) billing address from customer
         * -- shipping address (not same as billing)
         * -- -- Priority choosen is (1) customer; (2) request; (3) quote
         */
        if (!empty($address['same_as_billing']) || (is_object($billingAddress) && $billingAddress->getUseForShipping())) {
            if ($customerBillingAddress && $customerBillingAddress->getId()) {
                $shippingAddress->importCustomerAddress($customerBillingAddress);
            } else {
                if (!empty($requestBillingAddress)) {
                    $shippingAddress->addData($requestBillingAddress);
                } else {
                    $customerBillingAddress = $customer->getDefaultBillingAddress();
                    if ((!$shippingAddress || !$shippingAddress->getId()) && $customerBillingAddress && $customerBillingAddress->getId()) {
                        $shippingAddress->importCustomerAddress($customerBillingAddress);
                    }
                }
            }
        } else {
            if (empty($address)) return null;

            $customerShippingAddress = $customer->getAddressById($request->getParam('shipping_address_id'));
            if ($customerShippingAddress && $customerShippingAddress->getId()) {
                $shippingAddress->importCustomerAddress($customerShippingAddress);
            } else {
                if (!empty($address)) {
                    $shippingAddress->addData($address);
                } else {
                    $customerShippingAddress = $customer->getDefaultShippingAddress();
                    if ((!$shippingAddress || !$shippingAddress->getId()) && $customerShippingAddress && $customerShippingAddress->getId()) {
                        $shippingAddress->importCustomerAddress($customerShippingAddress);
                    }
                }
            }
        }

        if (!is_object($shippingAddress) || !$shippingAddress->getId()) {
            throw new Jmango360_Japi_Exception(Mage::helper('japi')->__("Customer shipping address not found."), Jmango360_Japi_Model_Request::HTTP_INTERNAL_ERROR);
        }

        if (!$shippingAddress->getEmail() || $request->getParam('email', false)) {
            $shippingAddress->setEmail($request->getParam('email', null));
            if (!$shippingAddress->getEmail() && (is_object($customerShippingAddress) && $customerShippingAddress->getEmail())) {
                $shippingAddress->setEmail($customerShippingAddress->getEmail());
            }
        }

        $shippingAddress->implodeStreetAddress();
        $shippingAddress->setSaveInAddressBook(!empty($address['save_in_address_book']));
        $shippingAddress->setUseForShipping(!empty($address['same_as_billing']));
        $shippingAddress->setCustomerAddressId($request->getParam('shipping_address_id', null));

        /**
         * If not set setCollectShippingRates(true) the shippingRates table is not populated
         */
        $shippingAddress->setCollectShippingRates(true);

        $errors = $this->_checkAddressData($shippingAddress);
        if (!empty($errors['error'])) {
            $message = implode("\n", $errors['message']);
            throw new Jmango360_Japi_Exception($message, Jmango360_Japi_Model_Request::HTTP_INTERNAL_ERROR);
        }

        return $shippingAddress->save();
    }

    /**
     * @return Mage_Sales_Model_Quote_Address
     * @throws Exception
     * @throws Jmango360_Japi_Exception
     */
    protected function _getBillingAddress()
    {
        $request = $this->_getRequest();
        $quote = $this->getQuote();
        $billingAddress = $quote->getBillingAddress();
        /* @var $customer Mage_Customer_Model_Customer */
        $customer = Mage::getSingleton('customer/session')->getCustomer();

        /**
         * $address in the request is optional in collectTotals, ass address is saved before in updateCartAddress
         * -- this function (public getBillingAddress) is used by updateCartAddress to save the address in the http request!
         */
        $address = $request->getParam('billing_address', array());
        if (empty($address)) return null;

        /**
         * Now we have a choice using: $billingAddress (quote); $address (request); $customerBillingAddress (customer)
         * -- Priority choosen is (1) customer; (2) request; (3) quote
         */
        $customerBillingAddress = $customer->getAddressById($request->getParam('billing_address_id'));

        if ($customerBillingAddress && $customerBillingAddress->getId()) {
            $billingAddress->importCustomerAddress($customerBillingAddress);
        } else {
            if (!empty($address)) {
                $billingAddress->addData($address);
            } else {
                $customerBillingAddress = $customer->getDefaultBillingAddress();
                if ((!$billingAddress || !$billingAddress->getId()) && $customerBillingAddress && $customerBillingAddress->getId()) {
                    $billingAddress->importCustomerAddress($customerBillingAddress);
                }
            }
        }

        if (!is_object($billingAddress) || !$billingAddress->getId()) {
            throw new Jmango360_Japi_Exception(Mage::helper('japi')->__("Customer billing address not found."), Jmango360_Japi_Model_Request::HTTP_INTERNAL_ERROR);
        }

        $billingAddress->implodeStreetAddress();
        $billingAddress->setSaveInAddressBook(!empty($address['save_in_address_book']));
        $billingAddress->setCustomerAddressId($request->getParam('billing_address_id', null));
        $billingAddress->setUseForShipping(!empty($address['use_for_shipping']));

        if (!$billingAddress->getEmail()) {
            $billingAddress->setEmail($request->getParam('email', null));
        }

        /**
         * MPLUGIN-1218: Set data customer name to quote from param 'billing_address
         */
        $quote->setCustomerFirstname(@$address['firstname']);
        $quote->setCustomerMiddlename(@$address['middlename']);
        $quote->setCustomerLastname(@$address['lastname']);

        $errors = $this->_checkAddressData($billingAddress);
        if (!empty($errors['error'])) {
            $message = implode("\n", $errors['message']);
            throw new Jmango360_Japi_Exception($message, Jmango360_Japi_Model_Request::HTTP_INTERNAL_ERROR);
        }

        return $billingAddress->save();
    }

    protected function _setQuoteCustomerInfo()
    {
        $customer = Mage::getSingleton('customer/session')->getCustomer();
        $quote = $this->getQuote();
        $request = $this->_getRequest();

        $quote->setCustomerEmail($request->getParam('email', null));
        if (!$quote->getCustomerEmail() && (is_object($customer) && $customer->getId())) {
            $quote->setCustomerEmail($customer->getEmail());
        }

        $quote->setCustomerDob($request->getParam('dob', null));
        if (!$quote->getCustomerDob() && (is_object($customer) && $customer->getId())) {
            $quote->setCustomerDob($customer->getDob());
        }

        $billingAddress = $quote->getBillingAddress();

        $quote->setCustomerNote($request->getParam('note', null));

        $quote->setCustomerPrefix($billingAddress->getPrefix());
        $quote->setCustomerFirstname($billingAddress->getFirstname());
        $quote->setCustomerLastname($billingAddress->getLastname());
        $quote->setCustomerMiddlename($billingAddress->getMiddlename());
        $quote->setCustomerPostfix($billingAddress->getPostfix());
    }

    protected function _preparePayment()
    {
        $request = $this->_getRequest();
        $paymentMethod = $request->getParam('payment_method', null);
        if (empty($paymentMethod)) {
            throw new Jmango360_Japi_Exception(Mage::helper('japi')->__('Payment method cannot be empty.'), Jmango360_Japi_Model_Request::HTTP_INTERNAL_ERROR);
        }

        $this->_validatePaymentMethod($paymentMethod);
        $data['method'] = $paymentMethod;

        /**
         * If ccsave is the chosen method
         *  -- no method check so it could be used for another creditcard save method
         */
        foreach ($request->getParam('payment_info', array()) as $key => $value) {
            if ($value !== '') {
                $data[$key] = $value;
            }
        }

        /**
         * For some reason Magento chose to empty the request and only add "$data" to the request
         *   -- possible security reasons
         *   thats why the billing address ahs to be set here again in $data
         *   -- not sure even of it is used; swithed of because dont think it is saved in the payment table anyway
         * @TODO: remove with next update
         */
// 	    $quoteBillingAddressData = $this->getQuote()->getBillingAddress();
// 	    foreach ($quoteBillingAddressData as $key => $value) {
// 	        if ($value !== '') {
// 	            $data[$key] = $value;
// 	        }
// 	    }
        $data['email'] = $request->getParam('email', null);
        if (empty($data['email'])) {
            $data['email'] = $this->getQuote()->getBillingAddress()->getEmail();
        }
        if (empty($data['email'])) {
            throw new Jmango360_Japi_Exception(Mage::helper('japi')->__('Email cannot be empty.'), Jmango360_Japi_Model_Request::HTTP_INTERNAL_ERROR);
        }

        /**
         * Get payment populates the quote _payments; this is used in the saveAfter saving the quote related objects
         *   -- triggers saving the payment
         */
        $this->_savePaymentInfo($data);

        return $this;
    }

    /**
     * There are no templates for creditcard data and issuerlists using the RESTfull interface
     * -- to create dynamic forms and add this additional data in the service call the getMethods
     * -- returns the form elements attributes and value to the RESTfull user-application and
     * -- returns them in the request as the array param "form"
     * When validating the form data (in the http request) the form data is saved in this object property "_paymentFormInfo"
     * -- this is add to the payment data and send through to the standard Magento payment validation method
     * -- called by "$this->savePayment($data);"
     * Validate the payment method and related getValueFromParam extracts the data and validates before it is
     * sent through to the "$this->savePayment($data);"
     */
    protected function _savePaymentInfo($data)
    {
        /**
         * Form data from the http request put in _paymentFormInfo during validation
         */
        $info = $this->_paymentFormInfo;
        if (!empty($info) || is_array($info)) {

            /**
             * Get all existing fieldnames from payment table
             */
            $paymentData = $this->getQuote()->getPayment()->getData();
            $paymentFields = array_keys($paymentData);

            /**
             * And extra fields not in payment table but used in payment data checks
             */
            foreach ($this->_extraPaymentFields as $extraFieldname) {
                $paymentFields[] = $extraFieldname;
            }

            /**
             * Add all params from request (Paypal Billing Agreement)
             */
            foreach ($info as $key => $value) {
                if (!in_array($key, $paymentFields)) {
                    $paymentFields[] = $key;
                }
            }

            /**
             * Add the payment form info in the request collected in $this->_paymentFormInfo during validation
             */
            foreach ($paymentFields as $fieldname) {
                /**
                 * If the same name in the request payment-form-fields is set
                 * -- add the value to data
                 */
                if (isset($info[$fieldname]) && !empty($info[$fieldname])) {
                    $data[$fieldname] = $info[$fieldname];
                }
            }
        }

        /**
         * Save all found payment data
         */
        $this->_resetPayment($data)->savePayment($data);
    }

    /**
     * If the quote payment row before is added with CC data
     * -- and the customer cancels and tries to use another method
     * -- the saved CC data can frustrate the payment checks (General Magento problem)
     * This why the data is reset first
     */
    protected function _resetPayment($data)
    {
        $payment = $this->getQuote()->getPayment();
        foreach ($this->_resetPaymentFieldnames as $fieldname) {
            $payment->setData($fieldname, null);
        }
        $payment->setData('method', $data['method']);
        $payment->save();

        return $this;
    }

    /**
     * Validate the payment method and related getValueFromParam extracts the data and validates before it is
     * sent through to the "$this->savePayment($data);"
     */
    protected function _validatePaymentMethod($paymentMethod)
    {
        $errors = array();

        /**
         * Check if shipping method exists and active
         */
        /* @var $checkoutMethods Jmango360_Japi_Model_Rest_Checkout_Methods */
        $checkoutMethods = Mage::getModel('japi/rest_checkout_methods');
        $availableMethods = $checkoutMethods->getValidatePaymentMethods($paymentMethod);

        if (empty($availableMethods[$paymentMethod])) {
            throw new Jmango360_Japi_Exception(Mage::helper('japi')->__('This payment method is currently not available.'), Jmango360_Japi_Model_Request::HTTP_INTERNAL_ERROR);
        }
        $method = $availableMethods[$paymentMethod];

        if (!empty($method['form'])) {
            foreach ($method['form'] as $element) {
                if (empty($element['label']) && !empty($element['id'])) {
                    $element['label'] = $element['id'];
                }
                if (empty($element['name']) && !empty($element['id'])) {
                    $element['name'] = $element['id'];
                }
                $value = $this->_getValueFromParam($element['name']);
                /**
                 * MPLUGIN-1146:
                 * Ignore validate value when installed "Netresearch_OPS" extension
                 * And using payment method "ops_cc" or "ops_dc" - this method not validate request data at server tier.
                 */
                $helper = Mage::helper('japi');
                $_needCheckValue = true;
                if ($helper->isModuleEnabled('Netresearch_OPS')) {
                    if ($paymentMethod == Netresearch_OPS_Model_Payment_Cc::CODE || $paymentMethod == 'ops_dc')
                        $_needCheckValue = false;
                }

                if ($_needCheckValue && empty($value)) {
                    if (!empty($element['class']) && stristr($element['class'], 'required-entry')) {
                        $errors[] = Mage::helper('japi')->__('Please add ') . trim($element['label'], '* \'"');
                    }
                }
            }
        }

        if (!empty($errors)) {
            throw new Jmango360_Japi_Exception(implode("\n", $errors), Jmango360_Japi_Model_Request::HTTP_INTERNAL_ERROR);
        }

        return $this;
    }

    /**
     * Validate the payment method and related getValueFromParam extracts the data and validates before it is
     * sent through to the "$this->savePayment($data);"
     */
    protected function _getValueFromParam($name)
    {
        $value = null;
        $request = $this->_getRequest();

        $requestForm = $request->getParam('form', array());
        if (empty($requestForm)) {
            return $value;
        }

        if (stristr($name, '[')) {
            $parts = explode('[', trim($name, ']'));
            if (!empty($parts[0]) && !empty($parts[1])) {
                $namespace = $parts[0];
                $name = $parts[1];
                if (!empty($requestForm[$namespace])) {
                    if (!empty($requestForm[$namespace][$name])) {
                        $value = $requestForm[$namespace][$name];
                        $param[$namespace] = $request->getParam($namespace, array());
                        $param[$namespace][$name] = $value;
                        $request->setParam($namespace, $param[$namespace]);
                        $this->_paymentFormInfo[$name] = $value;
                    }
                }
            }
        } else {
            $value = $requestForm[$name];
            $request->setParam($name, $value);
        }

        return $value;
    }

    protected function _checkAddressData($address)
    {
        /* @var $addressForm Mage_Customer_Model_Form */
        $addressForm = Mage::getModel('customer/form');
        $addressForm->setFormCode('customer_address_edit')
            ->setEntityType('customer_address')
            ->setIsAjaxRequest(false);
        $addressForm->setEntity($address);
        $addressErrors = $addressForm->validateData($address->getData());
        if ($addressErrors !== true) {
            return array('error' => 1, 'message' => $addressErrors);
        }
        return array('error' => 0, 'message' => array());
    }

    /**
     * Multi shipping is not supported
     */
    protected function _getCheckoutData()
    {
        $quote = $this->getQuote();
        $data['cart'] = $quote->getData();

        /**
         * Items are separated by product id instead of item ID
         *   -- the product ID is known in the App in the downloaded catalog
         */
        $index = 0;

        /* @var $helper Jmango360_Japi_Helper_Product */
        $helper = Mage::helper('japi/product');

        foreach ($quote->getAllVisibleItems() as $item) {
            /* @var $item Mage_Sales_Model_Quote_Item */
            $data['cart']['items'][$index] = $item->getData();
            $product = $helper->convertProductIdToApiResponseV2($item->getProductId());
            $data['cart']['items'][$index]['product'] = array($product);
            $data['cart']['items'][$index]['buy_request'] = $helper->getCartProductBuyRequest($item, $product);
            $index++;
        }

        /**
         * Multishipping could have more than 1 address and probably more than 1 totals
         *  -- multischipping is not supported yet --
         *  The totals are sorted in the right order, so can be displayed in this order too
         */
        foreach ($quote->getAllAddresses() as $address) {
            /* @var $address Mage_Sales_Model_Quote_Address */
            $data['cart']['addresses'][$address->getAddressType()] = $address->getData();
        }

        /**
         * Get totals
         */
        $data['cart']['totals'] = Mage::helper('japi')->getTotals($quote);

        /**
         * Add the checkout methods
         * -- as long as the order is not submitted changing the carts address and items
         * -- can change the checkout methods
         */
        /* @var $checkoutMethods Jmango360_Japi_Model_Rest_Checkout_Methods */
        $checkoutMethods = Mage::getModel('japi/rest_checkout_methods');
        $shippingMethods = $checkoutMethods->getShippingmethods();
        $data['cart']['shipping_methods'] = empty($shippingMethods) ? new stdClass() : $shippingMethods;
        $paymentMethods = $checkoutMethods->getPaymentMethods();
        $data['cart']['payment_methods'] = empty($paymentMethods) ? new stdClass() : $paymentMethods;

        $data['cart']['checkout_url'] = Mage::getUrl('japi/checkout/redirect', array(
            '_secure' => true,
            '_query' => array('SID' => Mage::getSingleton('core/session')->getSessionId())
        ));

        return $data;
    }
}
