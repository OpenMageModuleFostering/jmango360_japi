<?php

class Jmango360_Japi_Rest_CheckoutController extends Jmango360_Japi_Controller_Abstract
{
    public function getCheckoutMethodsAction()
    {
        $server = $this->getServer();
        $server->setRestDispatchModel(Mage::getModel('japi/rest_checkout'));
        $server->run();
    }

    public function updateCartAddressesAction()
    {
        $server = $this->getServer();
        $server->setRestDispatchModel(Mage::getModel('japi/rest_checkout'));
        $server->run();
    }

    public function collectTotalsAction()
    {
        $server = $this->getServer();
        $server->setRestDispatchModel(Mage::getModel('japi/rest_checkout'));
        $server->run();
    }

    public function SubmitOrderAction()
    {
        $server = $this->getServer();
        $server->setRestDispatchModel(Mage::getModel('japi/rest_checkout'));
        $server->run();
    }

    public function getPaymentRedirectAction()
    {
        $server = $this->getServer();
        $server->setRestDispatchModel(Mage::getModel('japi/rest_checkout'));
        $server->run();
    }

    public function updateShippingMethodAction()
    {
        $server = $this->getServer();
        $server->setRestDispatchModel(Mage::getModel('japi/rest_checkout'));
        $server->run();
    }

    public function updatePaymentMethodAction()
    {
        $server = $this->getServer();
        $server->setRestDispatchModel(Mage::getModel('japi/rest_checkout'));
        $server->run();
    }
}