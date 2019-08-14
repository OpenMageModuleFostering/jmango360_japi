<?php

class Jmango360_Japi_Rest_WishlistController extends Jmango360_Japi_Controller_Abstract
{
    public function getItemsAction()
    {
        $server = $this->getServer();
        $server->setRestDispatchModel(Mage::getModel('japi/rest_wishlist'));
        $server->run();
    }

    public function addAction()
    {
        $server = $this->getServer();
        $server->setRestDispatchModel(Mage::getModel('japi/rest_wishlist'));
        $server->run();
    }

    public function updateAction()
    {
        $server = $this->getServer();
        $server->setRestDispatchModel(Mage::getModel('japi/rest_wishlist'));
        $server->run();
    }

    public function removeAction()
    {
        $server = $this->getServer();
        $server->setRestDispatchModel(Mage::getModel('japi/rest_wishlist'));
        $server->run();
    }

    public function cartAction()
    {
        $server = $this->getServer();
        $server->setRestDispatchModel(Mage::getModel('japi/rest_wishlist'));
        $server->run();
    }

    public function allcartAction()
    {
        $server = $this->getServer();
        $server->setRestDispatchModel(Mage::getModel('japi/rest_wishlist'));
        $server->run();
    }

    public function updateItemOptionsAction()
    {
        $server = $this->getServer();
        $server->setRestDispatchModel(Mage::getModel('japi/rest_wishlist'));
        $server->run();
    }
}