<?php

class Jmango360_Japi_Model_Rest_Catalog_Category_Assignedproducts extends Mage_Catalog_Model_Category
{
    /**
     * Initialize requested category object
     *
     * @return Mage_Catalog_Model_Category
     * @throws Jmango360_Japi_Exception
     */
    public function getAssignedProducts()
    {
        $category = $this->_initCategory();

        /* @var $helper Jmango360_Japi_Helper_Product */
        $helper = Mage::helper('japi/product');

        if (is_object($category) && $category->getId()) {
            $block = $this->_getListBlock();
            $productCollection = $block->getLayer()->getProductCollection();
            /* @var $resource Mage_Core_Model_Resource */
            $resource = Mage::getSingleton('core/resource');
            $productCollection->getSelect()
                ->join(
                    array('p' => $resource->getTableName('catalog/product')),
                    sprintf(
                        'e.entity_id = p.entity_id AND p.type_id IN (%s)',
                        join(',', array('"simple"', '"configurable"', '"grouped"', '"bundle"'))
                    ),
                    null
                );

            if (!$productCollection->getSize()) {
                $data['message'] = $helper->__('No products found.');
            }

            $data['filters'] = $this->_getFilters();
            $helper->addPageSettings($productCollection);
            $data['toolbar_info'] = $helper->getToolBarInfo($productCollection);
            /**
             * Add group by product's ID to collection
             */
            if (version_compare(Mage::getVersion(), '1.9.2.1', '>')) {
                $productCollection->getSelect()->group('e.entity_id');
            }

            $productCollection->clear();
            $data['products'] = $helper->convertProductCollectionToApiResponse($productCollection);
        } else {
            throw new Jmango360_Japi_Exception(
                $helper->__('Category not found.'),
                Jmango360_Japi_Model_Request::HTTP_INTERNAL_ERROR
            );
        }

        return $data;
    }

    /**
     * @return Jmango360_Japi_Block_Catalog_Layer_View|Mage_Catalog_Block_Navigation
     */
    protected function _getLayerBlock()
    {
        /**
         * MPLUGIN-1601: Support Amasty_Shopby
         */
        if (Mage::helper('core')->isModuleEnabled('Amasty_Shopby')) {
            return Mage::helper('japi')->getBlock('Amasty_Shopby_Block_Catalog_Layer_View');
        }

        //skip non-anchor category
        $category = Mage::registry('current_category');
        if ($category->getIsAnchor()) {
            return Mage::helper('japi')->getBlock('Jmango360_Japi_Block_Catalog_Layer_View');
        } else {
            return Mage::helper('japi')->getBlock('Mage_Catalog_Block_Navigation');
        }
    }

    /**
     * @return Jmango360_Japi_Block_Catalog_Product_List
     */
    protected function _getListBlock()
    {
        return Mage::helper('japi')->getBlock('Jmango360_Japi_Block_Catalog_Product_List');
    }

    /**
     * @return Mage_Catalog_Block_Layer_State
     */
    protected function _getStateBlock()
    {
        return Mage::helper('japi')->getBlock('Mage_Catalog_Block_Layer_State');
    }

    /**
     * @return array
     */
    protected function _getFilters()
    {
        $data = array();
        $block = $this->_getLayerBlock();
        $filters = $block->getFilters();

        if (!$filters || !is_array($filters)) return $data;

        /* @var $helper Jmango360_Japi_Helper_Data */
        $helper = Mage::helper('japi');

        foreach ($filters as $key => $filter) {
            /* @var $filter Mage_Catalog_Block_Layer_Filter_Abstract */
            if ($filter->getType() == 'catalog/layer_filter_category') {
                continue;
            }
            if ($filter->getAttributeModel()
                && array_key_exists($filter->getAttributeModel()->getAttributeCode(), Mage::app()->getRequest()->getParams())
            ) {
                continue;
            }
            if ($filter->getItemsCount() && $helper->isFilterEnabled($filter, $block)) {
                $filterData = $this->_filterToArray($filter);
                if (!empty($filterData['items'])) $data[] = $filterData;
            }
        }

        return $data;
    }

    /**
     * Converts Mage_Catalog_Block_Layer_Filter_Abstract into array
     *
     * @param $filter Mage_Catalog_Block_Layer_Filter_Abstract
     * @return array
     */
    protected function _filterToArray($filter)
    {
        $data = array();
        $data['name'] = Mage::helper('japi')->__($filter->getName());
        $data['code'] = $filter->getAttributeModel()->getAttributeCode();
        if ($filter instanceof Amasty_Shopby_Block_Catalog_Layer_Filter_Attribute) {
            foreach ($filter->getItemsAsArray() as $item) {
                $data['items'][] = array(
                    'count' => $item['countValue'],
                    'label' => $item['label'],
                    'value' => $item['id'],
                    'url' => null
                );
            }
        } elseif ($filter instanceof Mage_Catalog_Block_Layer_Filter_Price) {
            if (class_exists('Amasty_Shopby_Model_Catalog_Layer_Filter_Price')) {
                if ($filter->hasDisplayType()
                    && in_array($filter->getDisplayType(), array(
                        Amasty_Shopby_Model_Catalog_Layer_Filter_Price::DT_DEFAULT,
                        Amasty_Shopby_Model_Catalog_Layer_Filter_Price::DT_DROPDOWN
                    ))
                ) {
                    foreach ($filter->getItems() as $item) {
                        $data['items'][] = $this->_itemToArray($item, $data['code']);
                    }
                }
            }
        } else {
            foreach ($filter->getItems() as $item) {
                $data['items'][] = $this->_itemToArray($item, $data['code']);
            }
        }

        return $data;
    }

    /**
     * Converts array Mage_Catalog_Model_Layer_Filter_Item into array
     *
     * @param $item Mage_Catalog_Model_Layer_Filter_Item
     * @param $code
     * @return array
     */
    protected function _itemToArray($item, $code)
    {
        /**
         * MPLUGIN-1144:
         * Fix isse filter when installed "Gomage_Navigation"
         * and set Use Friendly URLs to "Yes" in Admin config of this extension
         */
        /* @var $helper Jmango360_Japi_Helper_Product */
        $helper = Mage::helper('japi/product');
        $_label = (string)$item->getLabel();
        $_value = (string)$item->getValue();
        if ($helper->isModuleEnabled('GoMage_Navigation')) {
            $_ignoreAttrCode = array('price');
            if (!in_array($code, $_ignoreAttrCode)) {
                $gomageHelper = Mage::helper('gomage_navigation');
                if ($gomageHelper->isFrendlyUrl()) {
                    $_value = $gomageHelper->formatUrlValue($_label, $_value);
                }
            }
        }

        $data = array();
        $data['count'] = (int)$item->getCount();
        $data['label'] = $_label;
        $data['value'] = $_value;
        $data['url'] = null;

        return $data;
    }

    /**
     * Initialize requested category object
     *
     * @return Mage_Catalog_Model_Category
     * @throws Jmango360_Japi_Exception
     */
    protected function _initCategory()
    {
        /* @var $server Jmango360_Japi_Model_Server */
        $server = Mage::helper('japi')->getServer();
        /* @var $controller Jmango360_Japi_Rest_CatalogController */
        $controller = $server->getControllerInstance();

        Mage::dispatchEvent('catalog_controller_category_init_before', array('controller_action' => $controller));
        $categoryId = (int)Mage::helper('japi')->getRequest()->getParam('category_id', false);
        if (!$categoryId) {
            throw new Jmango360_Japi_Exception(
                Mage::helper('japi')->__('Category not found.'),
                Jmango360_Japi_Model_Request::HTTP_INTERNAL_ERROR
            );
        }

        /* @var $category Mage_Catalog_Model_Category */
        $category = Mage::getModel('catalog/category')
            ->setStoreId(Mage::app()->getStore()->getId())
            ->load($categoryId);

        if (!Mage::helper('catalog/category')->canShow($category)) {
            throw new Jmango360_Japi_Exception(
                Mage::helper('japi')->__('Category not available.'),
                Jmango360_Japi_Model_Request::HTTP_INTERNAL_ERROR
            );
        }

        Mage::getSingleton('catalog/session')->setLastVisitedCategoryId($category->getId());
        Mage::register('current_category', $category);
        Mage::register('current_entity_key', $category->getPath());

        try {
            Mage::dispatchEvent(
                'catalog_controller_category_init_after',
                array(
                    'category' => $category,
                    'controller_action' => $controller
                )
            );
        } catch (Mage_Core_Exception $e) {
            Mage::logException($e);
            return false;
        }

        return $category;
    }
}
