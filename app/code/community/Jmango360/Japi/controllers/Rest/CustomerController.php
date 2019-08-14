<?php

class Jmango360_Japi_Rest_CustomerController extends Jmango360_Japi_Controller_Abstract
{
    public function getCustomerAction()
    {
        $server = $this->getServer();
        $server->setRestDispatchModel(Mage::getModel('japi/rest_customer'));
        $server->run();
    }

    public function loginAction()
    {
        $server = $this->getServer();
        $server->setRestDispatchModel(Mage::getModel('japi/rest_customer'));
        $server->run();
    }

    public function logoutAction()
    {
        $server = $this->getServer();
        $server->setRestDispatchModel(Mage::getModel('japi/rest_customer'));
        $server->run();
    }

    public function registerAction()
    {
        $server = $this->getServer();
        $server->setRestDispatchModel(Mage::getModel('japi/rest_customer'));
        $server->run();
    }

    public function editAction()
    {
        $server = $this->getServer();
        $server->setRestDispatchModel(Mage::getModel('japi/rest_customer'));
        $server->run();
    }

    public function passwordresetAction()
    {
        $server = $this->getServer();
        $server->setRestDispatchModel(Mage::getModel('japi/rest_customer'));
        $server->run();
    }

    public function addressAction()
    {
        $server = $this->getServer();
        $server->setRestDispatchModel(Mage::getModel('japi/rest_customer'));
        $server->run();
    }

    public function getCustomerOrderListAction()
    {
        $server = $this->getServer();
        $server->setRestDispatchModel(Mage::getModel('japi/rest_customer'));
        $server->run();
    }

    public function getCustomerOrderDetailsAction()
    {
        $server = $this->getServer();
        $server->setRestDispatchModel(Mage::getModel('japi/rest_customer'));
        $server->run();
    }

    public function ordersAction()
    {
        $server = $this->getServer();
        $server->setRestDispatchModel(Mage::getModel('japi/rest_customer'));
        $server->run();
    }

    public function orderDetailsAction()
    {
        $server = $this->getServer();
        $server->setRestDispatchModel(Mage::getModel('japi/rest_customer'));
        $server->run();
    }

    public function groupsAction()
    {
        $server = $this->getServer();
        $server->setRestDispatchModel(Mage::getModel('japi/rest_customer'));
        $server->run();
    }

    public function groupAction()
    {
        $server = $this->getServer();
        $server->setRestDispatchModel(Mage::getModel('japi/rest_customer'));
        $server->run();
    }

    public function searchAction()
    {
        $server = $this->getServer();
        $server->setRestDispatchModel(Mage::getModel('japi/rest_customer'));
        $server->run();
    }
}