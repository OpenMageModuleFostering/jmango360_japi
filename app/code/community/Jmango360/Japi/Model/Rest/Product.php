<?php

class Jmango360_Japi_Model_Rest_Product extends Mage_Core_Model_Abstract
{
    public function dispatch()
    {
        $action = $this->_getRequest()->getAction();
        $operation = $this->_getRequest()->getOperation();

        switch ($action . $operation) {
            case 'list' . Jmango360_Japi_Model_Request::OPERATION_RETRIEVE:
                $data = $this->_getProductList();
                $this->_getResponse()->render($data);
                $this->_getResponse()->setHttpResponseCode(Jmango360_Japi_Model_Server::HTTP_OK);
                break;
            case 'detail' . Jmango360_Japi_Model_Request::OPERATION_RETRIEVE:
                $data = $this->_getProductDetail();
                $this->_getResponse()->render($data);
                $this->_getResponse()->setHttpResponseCode(Jmango360_Japi_Model_Server::HTTP_OK);
                break;
            case 'search' . Jmango360_Japi_Model_Request::OPERATION_RETRIEVE:
                $data = $this->_getProductSearch();
                $this->_getResponse()->render($data);
                $this->_getResponse()->setHttpResponseCode(Jmango360_Japi_Model_Server::HTTP_OK);
                break;
            case 'getRecentlyViewed' . Jmango360_Japi_Model_Request::OPERATION_RETRIEVE:
                $data = $this->_getRecentlyViewed();
                $this->_getResponse()->render($data);
                $this->_getResponse()->setHttpResponseCode(Jmango360_Japi_Model_Server::HTTP_OK);
                break;
            case 'getRecentlyPurchased' . Jmango360_Japi_Model_Request::OPERATION_RETRIEVE:
                $data = $this->_getRecentlyPurchased();
                $this->_getResponse()->render($data);
                $this->_getResponse()->setHttpResponseCode(Jmango360_Japi_Model_Server::HTTP_OK);
                break;
            case 'suggest' . Jmango360_Japi_Model_Request::OPERATION_RETRIEVE:
                $data = $this->_getProductSuggest();
                $this->_getResponse()->render($data);
                $this->_getResponse()->setHttpResponseCode(Jmango360_Japi_Model_Server::HTTP_OK);
                break;
            default:
                throw new Jmango360_Japi_Exception(
                    Mage::helper('japi')->__('Resource method not implemented'),
                    Jmango360_Japi_Model_Request::HTTP_INTERNAL_ERROR
                );
        }
    }

    protected function _getProductList()
    {
        /* @var $model Jmango360_Japi_Model_Rest_Product_List */
        $model = Mage::getModel('japi/rest_product_list');
        $data = $model->getList();

        return $data;
    }

    protected function _getProductSearch()
    {
        /* @var $model Jmango360_Japi_Model_Rest_Product_Search */
        $model = Mage::getModel('japi/rest_product_search');
        $data = $model->getList();

        return $data;
    }

    protected function _getProductSuggest()
    {
        /* @var $model Jmango360_Japi_Model_Rest_Product_Search */
        $model = Mage::getModel('japi/rest_product_search');
        $data = $model->getSuggest();

        return $data;
    }

    protected function _getProductDetail()
    {
        $id = $this->_getRequest()->getParam('product_id', 0);

        if (!$id || !is_numeric($id) || $id <= 0) {
            throw new Jmango360_Japi_Exception(
                Mage::helper('japi')->__('Product ID invalid'),
                Jmango360_Japi_Model_Request::HTTP_BAD_REQUEST
            );
        }

        $product = Mage::getModel('catalog/product')->load($id, array('sku'));
        if (!$product->getId()) {
            throw new Jmango360_Japi_Exception(
                Mage::helper('japi')->__('Product not found'),
                Jmango360_Japi_Model_Request::HTTP_INTERNAL_ERROR
            );
        }

        Mage::dispatchEvent('catalog_controller_product_view', array('product' => $product));

        /* @var $helper Jmango360_Japi_Helper_Product */
        $helper = Mage::helper('japi/product');
        $data['product'] = $helper->convertProductIdToApiResponseV2($id);
        if (!$data['product']) {
            throw new Jmango360_Japi_Exception(
                Mage::helper('japi')->__('Product not found'),
                Jmango360_Japi_Model_Request::HTTP_INTERNAL_ERROR
            );
        }

        return $data;
    }

    protected function _getRecentlyViewed()
    {
        /* @var $model Jmango360_Japi_Model_Rest_Product_Viewed */
        $model = Mage::getModel('japi/rest_product_viewed');
        $data = $model->getList();

        return $data;
    }

    protected function _getRecentlyPurchased()
    {
        if (!Mage::getSingleton('customer/session')->isLoggedIn()) {
            throw new Jmango360_Japi_Exception(
                Mage::helper('japi')->__('Please login first!'),
                Jmango360_Japi_Model_Request::HTTP_BAD_REQUEST
            );
        }
        /* @var $model Jmango360_Japi_Model_Rest_Product_Purchased */
        $model = Mage::getModel('japi/rest_product_purchased');
        $data = $model->getList();

        return $data;
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
