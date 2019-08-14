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

    public function getRelatedAction()
    {
        $server = $this->getServer();
        $server->setRestDispatchModel(Mage::getModel('japi/rest_product'));
        $server->run();
    }

    public function getCrossSellAction()
    {
        $server = $this->getServer();
        $server->setRestDispatchModel(Mage::getModel('japi/rest_product'));
        $server->run();
    }

    public function getUpSellAction()
    {
        $server = $this->getServer();
        $server->setRestDispatchModel(Mage::getModel('japi/rest_product'));
        $server->run();
    }

    public function getProductIdAction()
    {
        $server = $this->getServer();
        $server->setRestDispatchModel(Mage::getModel('japi/rest_product'));
        $server->run();
    }

    public function getReviewsAction()
    {
        $server = $this->getServer();
        $server->setRestDispatchModel(Mage::getModel('japi/rest_product'));
        $server->run();
    }

    public function getReviewFormAction()
    {
        $server = $this->getServer();
        $server->setRestDispatchModel(Mage::getModel('japi/rest_product'));
        $server->run();
    }

    public function saveReviewAction()
    {
        $server = $this->getServer();
        $server->setRestDispatchModel(Mage::getModel('japi/rest_product'));
        $server->run();
    }
}