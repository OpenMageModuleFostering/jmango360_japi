<?php

class Jmango360_Japi_Model_Rest_Customer_Address extends Jmango360_Japi_Model_Rest_Customer
{
    public function update()
    {
        $request = Mage::helper('japi')->getRequest();
        $this->_setAddressInRequest();

        $data = array();

        $customer = $this->_getSession()->getCustomer();
        /* @var $address Mage_Customer_Model_Address */
        $address = Mage::getModel('customer/address');
        $addressId = $request->getParam('address_id');
        if ($addressId) {
            $existsAddress = $customer->getAddressById($addressId);
            if ($existsAddress->getId() && $existsAddress->getCustomerId() == $customer->getId()) {
                $address->setId($existsAddress->getId());
            }
        }

        $errors = array();

        /* @var $addressForm Mage_Customer_Model_Form */
        $addressForm = Mage::getModel('customer/form');
        $addressForm->setFormCode('customer_address_edit')
            ->setEntity($address);
        $addressData = $addressForm->extractData($request);
        $addressErrors = $addressForm->validateData($addressData);
        if ($addressErrors !== true) {
            $errors = $addressErrors;
        }

        try {
            $addressForm->compactData($addressData);
            $address->setCustomerId($customer->getId())
                ->setIsDefaultBilling($request->getParam('default_billing', false))
                ->setIsDefaultShipping($request->getParam('default_shipping', false));

            $addressErrors = $address->validate();
            if ($addressErrors !== true) {
                $errors = array_merge($errors, $addressErrors);
            }

            if (count($errors) === 0) {
                $address->save();
            } else {
                $message = implode("\n", $errors);
                throw new Jmango360_Japi_Exception($message, Jmango360_Japi_Model_Request::HTTP_INTERNAL_ERROR);
            }
        } catch (Mage_Core_Exception $e) {
            throw new Jmango360_Japi_Exception($e->getMessage(), Jmango360_Japi_Model_Request::HTTP_INTERNAL_ERROR);
        } catch (Exception $e) {
            throw new Jmango360_Japi_Exception(Mage::helper('japi')->__('Cannot save the address: ' . $e->getMessage()), Jmango360_Japi_Model_Request::HTTP_INTERNAL_ERROR);
        }

        $data['customer'] = $this->_getCustomerData();

        return $data;
    }

    /*
     * Address data can be sent in a flat request
     * -- in case if it is sent in the way "register" sends it, it is in the array address
     * -- in that case the address array is set to the flat request   
     */
    protected function _setAddressInRequest()
    {
        $addressInfo = Mage::helper('japi')->getRequest()->getParam('address', array());
        if (!empty($addressInfo)) {
            foreach ($addressInfo as $name => $value) {
                Mage::app()->getRequest()->setParam($name, $value);
            }
        }

        return $this;
    }

    public function delete()
    {
        $request = Mage::helper('japi')->getRequest();
        $addressId = $request->getParam('address_id', false);
        $customer = $this->_getSession()->getCustomer();
        $defaultShippingAddress = $customer->getDefaultShippingAddress();
        $defaultBillingAddress = $customer->getDefaultBillingAddress();
        $message = '';

        if ($addressId) {
            $address = Mage::getModel('customer/address')->load($addressId);

            // Validate address_id <=> customer_id
            if ($address->getCustomerId() != $this->_getSession()->getCustomerId()) {
                throw new Jmango360_Japi_Exception(Mage::helper('japi')->__('The address does not belong to this customer.'), Jmango360_Japi_Model_Request::HTTP_INTERNAL_ERROR);
            }
            if (is_object($defaultBillingAddress) && $address->getId() == $defaultBillingAddress->getId()) {
                throw new Jmango360_Japi_Exception(Mage::helper('japi')->__('Default billing address can not be removed.'), Jmango360_Japi_Model_Request::HTTP_INTERNAL_ERROR);
            }
            if (is_object($defaultShippingAddress) && $address->getId() == $defaultShippingAddress->getId()) {
                throw new Jmango360_Japi_Exception(Mage::helper('japi')->__('Default shipping address can not be removed.'), Jmango360_Japi_Model_Request::HTTP_INTERNAL_ERROR);
            }

            try {
                $address->delete();
                $message = 'The address has been deleted.';
            } catch (Exception $e) {
                throw new Jmango360_Japi_Exception(Mage::helper('japi')->__('An error occurred while deleting the address.'), Jmango360_Japi_Model_Request::HTTP_INTERNAL_ERROR);
            }
        } else {
            throw new Jmango360_Japi_Exception(Mage::helper('japi')->__('Address ID can not be empty.'), Jmango360_Japi_Model_Request::HTTP_INTERNAL_ERROR);
        }

        $data['customer'] = $this->_getCustomerData();
        $data['message'] = $message;

        return $data;
    }
}
