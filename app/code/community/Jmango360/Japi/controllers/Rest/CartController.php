<?php

class Jmango360_Japi_Rest_CartController extends Jmango360_Japi_Controller_Abstract
{
    public function updateCartAction()
    {
        $server = $this->getServer();
        $server->setRestDispatchModel(Mage::getModel('japi/rest_cart'));
        $server->run();
    }

    public function updateCartItemAction()
    {
        $server = $this->getServer();
        $server->setRestDispatchModel(Mage::getModel('japi/rest_cart'));
        $server->run();
    }

    public function updateCouponAction()
    {
        $server = $this->getServer();
        $server->setRestDispatchModel(Mage::getModel('japi/rest_cart'));
        $server->run();
    }

    public function emptyCartAction()
    {
        $server = $this->getServer();
        $server->setRestDispatchModel(Mage::getModel('japi/rest_cart'));
        $server->run();
    }

    public function getCartAction()
    {
        $server = $this->getServer();
        $server->setRestDispatchModel(Mage::getModel('japi/rest_cart'));
        $server->run();
    }
}