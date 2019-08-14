<?php

class Jmango360_Japi_Model_Rest_Customer_Register extends Jmango360_Japi_Model_Rest_Customer
{
    public function register()
    {
        $this->_filterDates(array('dob'));

        /* @var $customer Mage_Customer_Model_Customer */
        $customer = Mage::getModel('customer/customer');

        if ($this->_getRequest()->getParam('is_subscribed', false)) {
            $customer->setIsSubscribed(1);
        }
        $customer->getGroupId();

        $errors = $this->_getCustomerErrors($customer);
        if (empty($errors)) {
            $this->cleanPasswordsValidationData($customer);

            // Flag mobile user
            $customer->setData('japi', 1);

            $customer->save();
            $this->_dispatchRegisterSuccess($customer);
            $this->_successProcessRegistration($customer);
        } else {
            $message = implode("\n", $errors);
            throw new Jmango360_Japi_Exception($message, Jmango360_Japi_Model_Request::HTTP_INTERNAL_ERROR);
        }

        if ($customer->isConfirmationRequired()) {
            $data = array(
                'messages' => array(
                    'message' => array(
                        array(
                            'code' => Mage_Customer_Model_Customer::EXCEPTION_EMAIL_NOT_CONFIRMED,
                            'message' => Mage::helper('japi')->__('Account is created. Please check email to confirm.'),
                            'type' => 1
                        )
                    )
                )
            );
            /*throw new Jmango360_Japi_Exception(
                Mage::helper('japi')->__('Account is created. Please check email to confirm.'),
                Jmango360_Japi_Model_Request::HTTP_INTERNAL_ERROR
            );*/
        } else {
            $data['customer'] = $this->_getCustomerData();
        }

        /*
         * Customer is logged in and therefor has a new session ID
         */
        $data['session_id'] = Mage::getSingleton('core/session')->getSessionId();

        return $data;
    }

    /**
     * Convert dates in array from localized to internal format
     */
    protected function _filterDates($dateFields)
    {
        $map = array(
            'D' => 'j',
            'MMMM' => 'm',
            'MMM' => 'm',
            'MM' => 'm',
            'M' => 'm',
            'dd' => 'd',
            'd' => 'd',
            'yyyy' => 'Y',
            'yy' => 'Y',
            'y' => 'Y'
        );
        $dateFormat = Mage::app()->getLocale()->getDateFormat(Mage_Core_Model_Locale::FORMAT_TYPE_SHORT);
        foreach ($map as $search => $replace) {
            $dateFormat = preg_replace('/(^|[^%])' . $search . '/', '$1' . $replace, $dateFormat);
        }
        $request = $this->_getRequest();
        if (!is_array($dateFields)) {
            $dateFields = array($dateFields);
        }
        foreach ($dateFields as $dateField) {
            if (!empty($dateField) && $data = $request->getParam($dateField)) {
                $request->setParam($dateField, date($dateFormat, strtotime($data)));
            }
        }
    }

    protected function _getCustomerErrors($customer)
    {
        $errors = array();
        $request = $this->_getRequest();

        if ($request->getParam('create_address') || $request->getParam('country_id') || $request->getParam('street')) {
            $errors = $this->_getErrorsOnCustomerAddress($customer);
        }
        $customerForm = $this->_getCustomerForm($customer);
        /*
         * the customer data from the request
         */
        $customerData = $customerForm->extractData($request);
        $customerErrors = $customerForm->validateData($customerData);
        if ($customerErrors !== true) {
            $errors = array_merge($customerErrors, $errors);
        } else {
            $customerForm->compactData($customerData);
            $customer->setPassword($request->getParam('password'));

            /*
             * Looks like both password_confirmation and confirmation are used due to version differences
             */
            $customer->setConfirmation($request->getParam('confirmation'));
            $customer->setPasswordConfirmation($request->getParam('confirmation'));

            $customerErrors = $customer->validate();
            if (is_array($customerErrors)) {
                $errors = array_merge($customerErrors, $errors);
            }
        }
        return $errors;
    }

    protected function _getErrorsOnCustomerAddress($customer)
    {
        $errors = array();
        /* @var $address Mage_Customer_Model_Address */
        $address = Mage::getModel('customer/address');
        /* @var $addressForm Mage_Customer_Model_Form */
        $addressForm = Mage::getModel('customer/form');
        $addressForm->setFormCode('customer_register_address')->setEntity($address);

        $addressData = $addressForm->extractData($this->_getRequest(), 'address', false);
        $addressErrors = $addressForm->validateData($addressData);
        if (is_array($addressErrors)) {
            $errors = array_merge($errors, $addressErrors);
        }
        $address->setId(null)
            ->setIsDefaultBilling($this->_getRequest()->getParam('default_billing', false))
            ->setIsDefaultShipping($this->_getRequest()->getParam('default_shipping', false));
        $addressForm->compactData($addressData);
        $customer->addAddress($address);

        $addressErrors = $address->validate();
        if (is_array($addressErrors)) {
            $errors = array_merge($errors, $addressErrors);
        }
        return $errors;
    }

    protected function _getCustomerForm($customer)
    {
        /* @var $customerForm Mage_Customer_Model_Form */
        $customerForm = Mage::getModel('customer/form');
        $customerForm->setFormCode('customer_account_create');
        $customerForm->setEntity($customer);
        return $customerForm;
    }

    protected function _dispatchRegisterSuccess($customer)
    {
        $controller = $this->_getServer()->getControllerInstance();
        Mage::dispatchEvent('customer_register_success',
            array('account_controller' => $controller, 'customer' => $customer));
    }

    protected function _successProcessRegistration(Mage_Customer_Model_Customer $customer)
    {
        $session = $this->_getSession();
        if ($customer->isConfirmationRequired()) {
            /** @var $app Mage_Core_Model_App */
            $app = Mage::app();
            /** @var $store  Mage_Core_Model_Store */
            $store = $app->getStore();
            $customer->sendNewAccountEmail(
                'confirmation',
                null,
                $store->getId()
            );
            $customerHelper = Mage::helper('customer');
            $session->addSuccess(Mage::helper('japi')->__("Account confirmation is required. Please, check your email for the confirmation link. To resend the confirmation email please <a href='%s'>click here</a>.",
                $customerHelper->getEmailConfirmationUrl($customer->getEmail())));
            /*
             * Because this is the App its not always possible to confirm through email.
             *   -- The customer is logged in and gets an email to confirm anyway
             *   -- The next time the customer want to logg in he gets a warning "not confirmed"
             */
        }
        $session->setCustomerAsLoggedIn($customer);

        return $this;
    }

    /* Clean password's validation data (password, password_confirmation)
     *
     * @return Mage_Customer_Model_Customer
     */
    public function cleanPasswordsValidationData($customer)
    {
        /*
         * Looks like both password_confirmation and confirmation are used due to version differences
        */
        $customer->setData('password', null);
        $customer->setData('confirmation', null);
        $customer->setData('password_confirmation', null);

        return $this;
    }
}
