<?php

class Jmango360_Japi_Rest_ProductController extends Jmango360_Japi_Controller_Abstract
{
    public function listAction()
    {
        $server = $this->getServer();
        $server->setRestDispatchModel(Mage::getModel('japi/rest_product'));
        $server->run();
    }

    public function detailAction()
    {
        $server = $this->getServer();
        $server->setRestDispatchModel(Mage::getModel('japi/rest_product'));
        $server->run();
    }

    public function searchAction()
    {
        $server = $this->getServer();
        $server->setRestDispatchModel(Mage::getModel('japi/rest_product'));
        $server->run();
    }

    public function getRecentlyViewedAction()
    {
        $server = $this->getServer();
        $server->setRestDispatchModel(Mage::getModel('japi/rest_product'));
        $server->run();
    }

    public function getRecentlyPurchasedAction()
    {
        $server = $this->getServer();
        $server->setRestDispatchModel(Mage::getModel('japi/rest_product'));
        $server->run();
    }

    public function suggestAction()
    {
        $server = $this->getServer();
        $server->setRestDispatchModel(Mage::getModel('japi/rest_product'));
        $server->run();
    }
}