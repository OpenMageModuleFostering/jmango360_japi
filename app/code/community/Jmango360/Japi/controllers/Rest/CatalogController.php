<?php

class Jmango360_Japi_Rest_CatalogController extends Jmango360_Japi_Controller_Abstract
{
    /*
     * @DEPRICATED is going to be replaced by the getCatalogProductList
     */
    public function getAssignedProductsAction()
    {
        $server = $this->getServer();
        $server->setRestDispatchModel(Mage::getModel('japi/rest_catalog'));
        $server->run();
    }

    /*
     * @TODO is going to replace the getAssigned products service call
     */
    public function getCatalogProductListAction()
    {
        $server = $this->getServer();
        $server->setRestDispatchModel(Mage::getModel('japi/rest_catalog'));
        $server->run();
    }

    public function getLayerFiltersAction()
    {
        $server = $this->getServer();
        $server->setRestDispatchModel(Mage::getModel('japi/rest_catalog'));
        $server->run();
    }

    public function getCategoryTreeAction()
    {
        $server = $this->getServer();
        $server->setRestDispatchModel(Mage::getModel('japi/rest_catalog'));
        $server->run();
    }

    public function getStockItemListAction()
    {
        $server = $this->getServer();
        $server->setRestDispatchModel(Mage::getModel('japi/rest_catalog'));
        $server->run();
    }

    public function searchProductsAction()
    {
        $server = $this->getServer();
        $server->setRestDispatchModel(Mage::getModel('japi/rest_catalog'));
        $server->run();
    }

    public function getProductAction()
    {
        $server = $this->getServer();
        $server->setRestDispatchModel(Mage::getModel('japi/rest_catalog'));
        $server->run();
    }

    public function getCategoryAction()
    {
        $server = $this->getServer();
        $server->setRestDispatchModel(Mage::getModel('japi/rest_catalog'));
        $server->run();
    }

    public function searchTermsAction()
    {
        $server = $this->getServer();
        $server->setRestDispatchModel(Mage::getModel('japi/rest_catalog'));
        $server->run();
    }
}