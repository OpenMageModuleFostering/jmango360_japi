<?php

class Jmango360_Japi_Model_Rest_Customer_Edit extends Jmango360_Japi_Model_Rest_Customer
{
    public function edit()
    {
        if (!$this->_getSession()->isLoggedIn()) {
            throw new Jmango360_Japi_Exception(Mage::helper('japi')->__('Please login.'), Jmango360_Japi_Model_Request::HTTP_INTERNAL_ERROR);
        }

        /** @var $customer Mage_Customer_Model_Customer */
        $customer = $this->_getSession()->getCustomer();

        /** @var $customerForm Mage_Customer_Model_Form */
        $customerForm = Mage::getModel('customer/form');
        $customerForm->setFormCode('customer_account_edit')
            ->setEntity($customer);

        $customerData = $customerForm->extractData($this->_getRequest());

        $errors = array();
        $customerErrors = $customerForm->validateData($customerData);
        if ($customerErrors !== true) {
            $errors = array_merge($customerErrors, $errors);
        } else {
            $customerForm->compactData($customerData);
            $errors = array();

            // If password change was requested then add it to common validation scheme
            if ($this->_getRequest()->getParam('change_password')) {
                $currPass = $this->_getRequest()->getParam('current_password');
                $newPass = $this->_getRequest()->getParam('password');
                $confPass = $this->_getRequest()->getParam('confirmation');

                $oldPass = $this->_getSession()->getCustomer()->getPasswordHash();
                if ($this->_getHelper('core/string')->strpos($oldPass, ':')) {
                    list($_salt, $salt) = explode(':', $oldPass);
                } else {
                    $salt = false;
                }

                if ($customer->hashPassword($currPass, $salt) == $oldPass) {
                    if (strlen($newPass)) {
                        /**
                         * Set entered password and its confirmation - they
                         * will be validated later to match each other and be of right length
                         */
                        $customer->setPassword($newPass);

                        /*
                         * Looks like both password_confirmation and confirmation are used due to version differences
                        */
                        $customer->setConfirmation($confPass);
                        $customer->setPasswordConfirmation($confPass);

                    } else {
                        $errors[] = Mage::helper('japi')->__('New password field cannot be empty.');
                    }
                } else {
                    $errors[] = Mage::helper('japi')->__('Invalid current password');
                }
            }

            // Validate account and compose list of errors if any
            $customerErrors = $customer->validate();
            if (is_array($customerErrors)) {
                $errors = array_merge($errors, $customerErrors);
            }
        }

        if (!empty($errors)) {
            $message = implode("\n", $errors);
            throw new Jmango360_Japi_Exception($message, Jmango360_Japi_Model_Request::HTTP_INTERNAL_ERROR);
        }

        try {
            $this->cleanPasswordsValidationData($customer);
            $customer->save();
            $this->_getSession()->setCustomer($customer);
            $data['message'] = Mage::helper('japi')->__('The account information has been saved.');
            $data['customer'] = $this->_getCustomerData();

            return $data;

        } catch (Mage_Core_Exception $e) {
            throw new Jmango360_Japi_Exception($e->getMessage(), Jmango360_Japi_Model_Request::HTTP_INTERNAL_ERROR);
        } catch (Exception $e) {
            throw new Jmango360_Japi_Exception($e->getMessage(), Jmango360_Japi_Model_Request::HTTP_INTERNAL_ERROR);
        }
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
