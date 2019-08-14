<?php

class Jmango360_Japi_Model_Rest_Catalog extends Mage_Catalog_Model_Abstract
{
    public function dispatch()
    {
        $action = $this->_getRequest()->getAction();
        $operation = $this->_getRequest()->getOperation();

        switch ($action . $operation) {
            /*
             * @DEPRICATED is going to be replaced by the getCatalogProductList
            */
            case 'getAssignedProducts' . Jmango360_Japi_Model_Request::OPERATION_RETRIEVE:
                $data = $this->_getAssignedProducts();
                $this->_getResponse()->render($data);
                $this->_getResponse()->setHttpResponseCode(Jmango360_Japi_Model_Server::HTTP_OK);
                break;
            /*
             * @TODO is going to replace the getAssigned products service call
            */
            case 'getCatalogProductList' . Jmango360_Japi_Model_Request::OPERATION_RETRIEVE:
                $data = $this->_getCatalogProductList();
                $this->_getResponse()->render($data);
                $this->_getResponse()->setHttpResponseCode(Jmango360_Japi_Model_Server::HTTP_OK);
                break;
            case 'getLayerFilters' . Jmango360_Japi_Model_Request::OPERATION_RETRIEVE:
                $data = $this->_getLayerFilters();
                $this->_getResponse()->render($data);
                $this->_getResponse()->setHttpResponseCode(Jmango360_Japi_Model_Server::HTTP_OK);
                break;
            case 'getCategoryTree' . Jmango360_Japi_Model_Request::OPERATION_RETRIEVE:
                $data = $this->_getCategoryTree();
                $this->_getResponse()->render($data);
                $this->_getResponse()->setHttpResponseCode(Jmango360_Japi_Model_Server::HTTP_OK);
                break;
            case 'getStockItemList' . Jmango360_Japi_Model_Request::OPERATION_RETRIEVE:
                $data = $this->_getStockItemList();
                $this->_getResponse()->render($data);
                $this->_getResponse()->setHttpResponseCode(Jmango360_Japi_Model_Server::HTTP_OK);
                break;
            case 'searchProducts' . Jmango360_Japi_Model_Request::OPERATION_RETRIEVE:
                $data = $this->_searchProducts();
                $this->_getResponse()->render($data);
                $this->_getResponse()->setHttpResponseCode(Jmango360_Japi_Model_Server::HTTP_OK);
                break;
            case 'getProduct' . Jmango360_Japi_Model_Request::OPERATION_RETRIEVE:
                $data = $this->_getProduct();
                $this->_getResponse()->render($data);
                $this->_getResponse()->setHttpResponseCode(Jmango360_Japi_Model_Server::HTTP_OK);
                break;
            case 'getCategory' . Jmango360_Japi_Model_Request::OPERATION_RETRIEVE:
                $data = $this->_getCategory();
                $this->_getResponse()->render($data);
                $this->_getResponse()->setHttpResponseCode(Jmango360_Japi_Model_Server::HTTP_OK);
                break;
            case 'searchTerms' . Jmango360_Japi_Model_Request::OPERATION_RETRIEVE:
                $data = $this->_getSearchTerms();
                $this->_getResponse()->render($data);
                $this->_getResponse()->setHttpResponseCode(Jmango360_Japi_Model_Server::HTTP_OK);
                break;
            default:
                throw new Jmango360_Japi_Exception(Mage::helper('japi')->__('Resource method not implemented'), Jmango360_Japi_Model_Request::HTTP_INTERNAL_ERROR);
                break;
        }
    }

    protected function _getSearchTerms()
    {
        /* @var $model Jmango360_Japi_Model_Rest_Catalog_Search_Terms */
        $model = Mage::getModel('japi/rest_catalog_search_terms');
        $data = $model->getTerms();

        return $data;
    }

    protected function _searchProducts()
    {
        /* @var $model Jmango360_Japi_Model_Rest_Catalog_Search_Products */
        $model = Mage::getModel('japi/rest_catalog_search_products');
        $data = $model->getList();

        return $data;
    }

    protected function _getProduct()
    {
        $id = $this->_getRequest()->getParam('product_id', 0);

        if (!$id || !is_numeric($id) || $id <= 0) {
            throw new Jmango360_Japi_Exception(
                Mage::helper('japi')->__('Product ID invalid'),
                Jmango360_Japi_Model_Request::HTTP_BAD_REQUEST
            );
        }

        /* @var $helper Jmango360_Japi_Helper_Product */
        $helper = Mage::helper('japi/product');
        $data['product'] = $helper->convertProductIdToApiResponse($id);

        return $data;
    }

    protected function _getCategory()
    {
        $id = $this->_getRequest()->getParam('category_id', 0);

        /* @var $model Jmango360_Japi_Model_Rest_Catalog_Category_Tree */
        $model = Mage::getModel('japi/rest_catalog_category_tree');
        $data = $model->category($id);

        return $data;
    }

    protected function _getCategoryTree()
    {
        /* @var $model Jmango360_Japi_Model_Rest_Catalog_Category_Tree */
        $model = Mage::getModel('japi/rest_catalog_category_tree');
        $data = $model->tree();

        return $data;
    }

    /*
     * @DEPRICATED is going to be replaced by the getCatalogProductList
     */
    protected function _getAssignedProducts()
    {
        /* @var $model Jmango360_Japi_Model_Rest_Catalog_Category_Assignedproducts */
        $model = Mage::getModel('japi/rest_catalog_category_assignedproducts');
        $data = $model->getAssignedProducts();

        return $data;
    }

    /*
     * @TODO is going to replace the getAssigned products service call
     */
    protected function _getCatalogProductList()
    {
        /* @var $model Jmango360_Japi_Model_Rest_Catalog_Category_Products */
        $model = Mage::getModel('japi/rest_catalog_category_products');
        $data = $model->getlist();

        return $data;
    }

    protected function _getLayerFilters()
    {
        /* @var $model Jmango360_Japi_Model_Rest_Catalog_Layer_Layerfilters */
        $model = Mage::getModel('japi/rest_catalog_layer_layerfilters');
        $data = $model->getLayerFilters();

        return $data;
    }

    protected function _getStockItemList()
    {
        /* @var $model Jmango360_Japi_Model_Rest_Catalog_Stock_List */
        $model = Mage::getModel('japi/rest_catalog_stock_list');
        $data = $model->getItems();

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
