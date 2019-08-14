<?php

class Jmango360_Japi_Model_Rest_Customer extends Mage_Customer_Model_Customer
{
    public function dispatch()
    {
        $action = $this->_getRequest()->getAction();
        $operation = $this->_getRequest()->getOperation();

        switch ($action . $operation) {
            case 'getCustomer' . Jmango360_Japi_Model_Request::OPERATION_RETRIEVE:
                $this->_checkSession();
                $data = $this->_getCustomer();
                $this->_getResponse()->render($data);
                $this->_getResponse()->setHttpResponseCode(Jmango360_Japi_Model_Server::HTTP_CREATED);
                break;
            case 'register' . Jmango360_Japi_Model_Request::OPERATION_CREATE:
                $data = $this->_register();
                $this->_getResponse()->render($data);
                $this->_getResponse()->setHttpResponseCode(Jmango360_Japi_Model_Server::HTTP_CREATED);
                break;
            case 'edit' . Jmango360_Japi_Model_Request::OPERATION_UPDATE:
                $this->_checkSession();
                $data = $this->_edit();
                $this->_getResponse()->render($data);
                $this->_getResponse()->setHttpResponseCode(Jmango360_Japi_Model_Server::HTTP_CREATED);
                break;
            case 'passwordreset' . Jmango360_Japi_Model_Request::OPERATION_UPDATE:
                $data = $this->_passwordreset();
                $this->_getResponse()->render($data);
                $this->_getResponse()->setHttpResponseCode(Jmango360_Japi_Model_Server::HTTP_CREATED);
                break;

            case 'login' . Jmango360_Japi_Model_Request::OPERATION_CREATE:
                $data = $this->_login();
                $this->_getResponse()->render($data);
                $this->_getResponse()->setHttpResponseCode(Jmango360_Japi_Model_Server::HTTP_OK);
                break;
            case 'logout' . Jmango360_Japi_Model_Request::OPERATION_CREATE:
                $this->_checkSession();
                $data = $this->_logout();
                $this->_getResponse()->render($data);
                $this->_getResponse()->setHttpResponseCode(Jmango360_Japi_Model_Server::HTTP_OK);
                break;

            case 'getList' . Jmango360_Japi_Model_Request::OPERATION_RETRIEVE:
                $data = $this->_customerList();
                $this->_getResponse()->render($data);
                $this->_getResponse()->setHttpResponseCode(Jmango360_Japi_Model_Server::HTTP_OK);
                break;

            case 'address' . Jmango360_Japi_Model_Request::OPERATION_CREATE:
                $this->_checkSession();
                $data = $this->_createAddress();
                $this->_getResponse()->render($data);
                $this->_getResponse()->setHttpResponseCode(Jmango360_Japi_Model_Server::HTTP_CREATED);
                break;
            case 'address' . Jmango360_Japi_Model_Request::OPERATION_UPDATE:
                $this->_checkSession();
                $data = $this->_updateAddress();
                $this->_getResponse()->render($data);
                $this->_getResponse()->setHttpResponseCode(Jmango360_Japi_Model_Server::HTTP_CREATED);
                break;
            case 'address' . Jmango360_Japi_Model_Request::OPERATION_DELETE:
                $this->_checkSession();
                $data = $this->_deleteAddress();
                $this->_getResponse()->render($data);
                $this->_getResponse()->setHttpResponseCode(Jmango360_Japi_Model_Server::HTTP_OK);
                break;

            case 'getCustomerOrderList' . Jmango360_Japi_Model_Request::OPERATION_RETRIEVE:
                $this->_checkSession();
                $data = $this->_getCustomerOrderList();
                $this->_getResponse()->render($data);
                $this->_getResponse()->setHttpResponseCode(Jmango360_Japi_Model_Server::HTTP_OK);
                break;
            case 'getCustomerOrderDetails' . Jmango360_Japi_Model_Request::OPERATION_RETRIEVE:
                $this->_checkSession();
                $data = $this->_getCustomerOrderDetails();
                $this->_getResponse()->render($data);
                $this->_getResponse()->setHttpResponseCode(Jmango360_Japi_Model_Server::HTTP_OK);
                break;

            case 'orders' . Jmango360_Japi_Model_Request::OPERATION_RETRIEVE:
                $this->_checkSession();
                $data = $this->_getOrders();
                $this->_getResponse()->render($data);
                $this->_getResponse()->setHttpResponseCode(Jmango360_Japi_Model_Server::HTTP_OK);
                break;
            case 'orderDetails' . Jmango360_Japi_Model_Request::OPERATION_RETRIEVE:
                $this->_checkSession();
                $data = $this->_getOrderDetails();
                $this->_getResponse()->render($data);
                $this->_getResponse()->setHttpResponseCode(Jmango360_Japi_Model_Server::HTTP_OK);
                break;

            case 'groups' . Jmango360_Japi_Model_Request::OPERATION_RETRIEVE:
                $data = $this->_getCustomerGroups();
                $this->_getResponse()->render($data);
                $this->_getResponse()->setHttpResponseCode(Jmango360_Japi_Model_Server::HTTP_OK);
                break;
            case 'group' . Jmango360_Japi_Model_Request::OPERATION_RETRIEVE:
                $data = $this->_getGroupCustomers();
                $this->_getResponse()->render($data);
                $this->_getResponse()->setHttpResponseCode(Jmango360_Japi_Model_Server::HTTP_OK);
                break;
            case 'search' . Jmango360_Japi_Model_Request::OPERATION_RETRIEVE:
                $data = $this->_getSearchCustomers();
                $this->_getResponse()->render($data);
                $this->_getResponse()->setHttpResponseCode(Jmango360_Japi_Model_Server::HTTP_OK);
                break;

            default:
                throw new Jmango360_Japi_Exception(Mage::helper('japi')->__('Resource method not implemented'), Jmango360_Japi_Model_Request::HTTP_INTERNAL_ERROR);
                break;
        }
    }

    protected function _getSearchCustomers()
    {
        /* @var $model Jmango360_Japi_Model_Rest_Customer_Group */
        $model = Mage::getModel('japi/rest_customer_group');
        return $model->search();
    }

    protected function _getCustomerGroups()
    {
        /* @var $model Jmango360_Japi_Model_Rest_Customer_Group */
        $model = Mage::getModel('japi/rest_customer_group');
        return $model->getList();
    }

    protected function _getGroupCustomers()
    {
        /* @var $model Jmango360_Japi_Model_Rest_Customer_Group */
        $model = Mage::getModel('japi/rest_customer_group');
        return $model->getCustomers();
    }

    protected function _getCustomerOrderList()
    {
        /* @var $model Jmango360_Japi_Model_Rest_Customer_Order */
        $model = Mage::getModel('japi/rest_customer_order');
        $data = $model->getCustomerOrderList();

        return $data;
    }

    protected function _getOrders()
    {
        /* @var $model Jmango360_Japi_Model_Rest_Customer_Order_List */
        $model = Mage::getModel('japi/rest_customer_order_list');
        $data = $model->getOrderList();

        return $data;
    }

    protected function _getCustomerOrderDetails()
    {
        /* @var $model Jmango360_Japi_Model_Rest_Customer_Order */
        $model = Mage::getModel('japi/rest_customer_order');
        $data = $model->getCustomerOrderDetails();

        return $data;
    }

    protected function _getOrderDetails()
    {
        /* @var $model Jmango360_Japi_Model_Rest_Customer_Order_List */
        $model = Mage::getModel('japi/rest_customer_order_list');
        $data = $model->getOrderDetails();

        return $data;
    }

    protected function _createAddress()
    {
        /* @var $model Jmango360_Japi_Model_Rest_Customer_Address */
        $model = Mage::getModel('japi/rest_customer_address');
        $data = $model->update();

        return $data;
    }

    protected function _updateAddress()
    {
        /* @var $model Jmango360_Japi_Model_Rest_Customer_Address */
        $model = Mage::getModel('japi/rest_customer_address');
        $data = $model->update();

        return $data;
    }

    protected function _deleteAddress()
    {
        /* @var $model Jmango360_Japi_Model_Rest_Customer_Address */
        $model = Mage::getModel('japi/rest_customer_address');
        $data = $model->delete();

        return $data;
    }

    protected function _customerList()
    {
        /* @var $model Jmango360_Japi_Model_Rest_Customer_List */
        $model = Mage::getModel('japi/rest_customer_list');
        $data = $model->getList();

        return $data;
    }

    protected function _register()
    {
        /* @var $model Jmango360_Japi_Model_Rest_Customer_Register */
        $model = Mage::getModel('japi/rest_customer_register');
        $data = $model->register();

        return $data;
    }

    protected function _edit()
    {
        /* @var $model Jmango360_Japi_Model_Rest_Customer_Edit */
        $model = Mage::getModel('japi/rest_customer_edit');
        $data = $model->edit();

        return $data;
    }

    protected function _passwordreset()
    {
        /* @var $model Jmango360_Japi_Model_Rest_Customer_Password */
        $model = Mage::getModel('japi/rest_customer_password');
        $data = $model->passwordreset();

        return $data;
    }

    protected function _login()
    {
        /* @var $model Jmango360_Japi_Model_Rest_Customer_Login */
        $model = Mage::getModel('japi/rest_customer_login');
        $data = $model->login();

        return $data;
    }

    protected function _logout()
    {
        $this->_getSession()->logout()->renewSession();
        if ($this->_getSession()->isLoggedIn()) {
            throw new Jmango360_Japi_Exception(
                Mage::helper('japi')->__('Could not log-out customer'),
                Jmango360_Japi_Model_Request::HTTP_INTERNAL_ERROR
            );
        }

        /**
         * Fix for MPLUGIN-980: make sure session's data "customer_id" and "id" is delete when customer logged-in
         */
        $this->_getSession()->setData('customer_id', null);
        $this->_getSession()->setData('id', null);

        //Mage::log('refreshed session for the customer is logged out', null, 'token.log');
        $data['status'] = 'logged_out';
        $data['session_id'] = Mage::getSingleton('core/session')->getSessionId();

        return $data;
    }

    protected function _checkSession()
    {
        if (!$this->_getSession()->isLoggedIn()) {
            throw new Jmango360_Japi_Exception(
                Mage::helper('japi')->__('Please login.'),
                Jmango360_Japi_Model_Request::REST_CUSTOMER_EXPIRED
            );
        }
    }

    protected function _getCustomer()
    {
        return array('customer' => $this->_getCustomerData());
    }

    protected function _getCustomerData()
    {
        $data = array();
        if (!$this->_getSession()->isLoggedIn()) {
            return $data;
        }

        /*
         * It can happen that after saving customer the customer is not set in session
         * -- and some saved data is not shown
         * -- to be sure the customer is loaded again before setting the response 
         */
        $customerId = $this->_getSession()->getCustomerId();
        /* @var $customer Mage_Customer_Model_Customer */
        $customer = Mage::getModel('customer/customer')->load($customerId);

        $data = $customer->getData();
        $data['addresses'] = array();
        $index = 0;
        /* @var $helper Jmango360_Japi_Helper_Data */
        $helper = Mage::helper('japi');
        foreach ($customer->getAddressesCollection() as $address) {
            /* @var $address Mage_Customer_Model_Address */
            $data['addresses'][$index] = $address->getData();
            $data['addresses'][$index]['country'] = $helper->getCountryById($address->getCountryId());
            //$data['addresses'][$index]['region'] = $helper->getRegionById($address->getCountryId(), $address->getRegionId());
            $data['addresses'][$index]['region'] = $address->getRegion();
            $index++;
        }

        if (empty($data['default_billing'])) {
            $defaultBillingAddress = $customer->getDefaultBillingAddress();
            $data['default_billing'] = (is_object($defaultBillingAddress) && $defaultBillingAddress->getId()) ? $defaultBillingAddress->getId() : null;
        }

        if (empty($data['default_shipping'])) {
            $defaultShippingAddress = $customer->getDefaultShippingAddress();
            $data['default_shipping'] = (is_object($defaultShippingAddress) && $defaultShippingAddress->getId()) ? $defaultShippingAddress->getId() : null;
        }

        return $data;
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