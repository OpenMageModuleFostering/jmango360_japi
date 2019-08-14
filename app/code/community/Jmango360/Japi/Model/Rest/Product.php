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
            case 'getRelated' . Jmango360_Japi_Model_Request::OPERATION_RETRIEVE:
                $data = $this->_getRelatedProducts();
                $this->_getResponse()->render($data);
                $this->_getResponse()->setHttpResponseCode(Jmango360_Japi_Model_Server::HTTP_OK);
                break;
            case 'getCrossSell' . Jmango360_Japi_Model_Request::OPERATION_RETRIEVE:
                $data = $this->_getCrossSellProducts();
                $this->_getResponse()->render($data);
                $this->_getResponse()->setHttpResponseCode(Jmango360_Japi_Model_Server::HTTP_OK);
                break;
            case 'getUpSell' . Jmango360_Japi_Model_Request::OPERATION_RETRIEVE:
                $data = $this->_getUpSellProducts();
                $this->_getResponse()->render($data);
                $this->_getResponse()->setHttpResponseCode(Jmango360_Japi_Model_Server::HTTP_OK);
                break;
            case 'getProductId' . Jmango360_Japi_Model_Request::OPERATION_RETRIEVE:
                $data = $this->_getProductId();
                $this->_getResponse()->render($data);
                $this->_getResponse()->setHttpResponseCode(Jmango360_Japi_Model_Server::HTTP_OK);
                break;
            case 'getReviews' . Jmango360_Japi_Model_Request::OPERATION_RETRIEVE:
                $data = $this->_getProductReviews();
                $this->_getResponse()->render($data);
                $this->_getResponse()->setHttpResponseCode(Jmango360_Japi_Model_Server::HTTP_OK);
                break;
            case 'getReviewForm' . Jmango360_Japi_Model_Request::OPERATION_RETRIEVE:
                $data = $this->_getProductReviewForm();
                $this->_getResponse()->render($data);
                $this->_getResponse()->setHttpResponseCode(Jmango360_Japi_Model_Server::HTTP_OK);
                break;
            case 'saveReview' . Jmango360_Japi_Model_Request::OPERATION_CREATE:
                $data = $this->_saveProductReview();
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

    protected function _getProductReviews()
    {
        /* @var $model Jmango360_Japi_Model_Rest_Product_Review */
        $model = Mage::getModel('japi/rest_product_review');
        $data = $model->getList();

        return $data;
    }

    protected function _saveProductReview()
    {
        /* @var $model Jmango360_Japi_Model_Rest_Product_Review */
        $model = Mage::getModel('japi/rest_product_review');
        $data = $model->saveReview();

        return $data;
    }

    protected function _getProductReviewForm()
    {
        /* @var $model Jmango360_Japi_Model_Rest_Product_Review */
        $model = Mage::getModel('japi/rest_product_review');
        $data = $model->getForm();

        return $data;
    }

    protected function _getProductId()
    {
        /* @var $model Jmango360_Japi_Model_Rest_Product_Url */
        $model = Mage::getModel('japi/rest_product_url');
        $data = $model->getProductId();

        return $data;
    }

    protected function _getRelatedProducts()
    {
        /* @var $model Jmango360_Japi_Model_Rest_Product_Related */
        $model = Mage::getModel('japi/rest_product_related');
        $data = $model->getList();

        return $data;
    }

    protected function _getCrossSellProducts()
    {
        /* @var $model Jmango360_Japi_Model_Rest_Product_Crosssell */
        $model = Mage::getModel('japi/rest_product_crosssell');
        $data = $model->getList();

        return $data;
    }

    protected function _getUpSellProducts()
    {
        /* @var $model Jmango360_Japi_Model_Rest_Product_Upsell */
        $model = Mage::getModel('japi/rest_product_upsell');
        $data = $model->getList();

        return $data;
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
        $product = $this->_initProduct();

        Mage::dispatchEvent('catalog_controller_product_view', array('product' => $product));

        /* @var $helper Jmango360_Japi_Helper_Product */
        $helper = Mage::helper('japi/product');
        $data['product'] = $helper->convertProductIdToApiResponseV2($product->getId());
        if (!$data['product']) {
            throw new Jmango360_Japi_Exception(
                Mage::helper('japi')->__('Product not found'),
                Jmango360_Japi_Model_Request::HTTP_INTERNAL_ERROR
            );
        }

        return $data;
    }

    /**
     * @return Mage_Catalog_Model_Product
     * @throws Jmango360_Japi_Exception
     */
    protected function _initProduct()
    {
        $id = $this->_getRequest()->getParam('product_id', 0);

        if (!$id || !is_numeric($id) || $id <= 0) {
            throw new Jmango360_Japi_Exception(
                Mage::helper('japi')->__('Product ID invalid'),
                Jmango360_Japi_Model_Request::HTTP_BAD_REQUEST
            );
        }

        $product = Mage::getModel('catalog/product')->load($id, array('sku', 'hide_in_jm360'));

        if (!$product->getId() || $product->getData('hide_in_jm360') == 1) {
            throw new Jmango360_Japi_Exception(
                Mage::helper('japi')->__('Product not found'),
                Jmango360_Japi_Model_Request::HTTP_INTERNAL_ERROR
            );
        }

        /* @var $reviewHelper Jmango360_Japi_Helper_Product_Review */
        $reviewHelper = Mage::helper('japi/product_review');
        if ($reviewHelper->isReviewEnable()) {
            /* @var $reviewModel Mage_Review_Model_Review */
            $reviewModel = Mage::getModel('review/review');
            $reviewModel->getEntitySummary($product, Mage::app()->getStore()->getId());
        }

        Mage::register('current_product', $product);

        return $product;
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
