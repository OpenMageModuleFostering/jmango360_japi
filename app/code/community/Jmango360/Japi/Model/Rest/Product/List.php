<?php

class Jmango360_Japi_Model_Rest_Product_List extends Jmango360_Japi_Model_Rest_Catalog_Category_Assignedproducts
{
    public function getList()
    {
        $category = $this->_initCategory();

        /* @var $helper Jmango360_Japi_Helper_Product */
        $helper = Mage::helper('japi/product');

        if (is_object($category) && $category->getId()) {
            $block = $this->_getListBlock();
            $productCollection = $block->getLayer()->getProductCollection();
            Mage::helper('japi/product')->applyHideOnAppFilter($productCollection);

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
                $data['message'] = Mage::helper('japi')->__('No products found.');
            }

            $data['filters'] = $this->_getFilters();
            $helper->addPageSettings($productCollection);
            $data['toolbar_info'] = $helper->getToolbarInfo($productCollection);

            $productCollection->clear();
            $data['products'] = $helper->convertProductCollectionToApiResponseV2($productCollection);
        } else {
            throw new Jmango360_Japi_Exception(
                Mage::helper('japi')->__('Category not found.'),
                Jmango360_Japi_Model_Request::HTTP_INTERNAL_ERROR
            );
        }

        return $data;
    }

    public function getListByIds()
    {
        /* @var $request Jmango360_Japi_Model_Request */
        $request = Mage::helper('japi')->getRequest();

        $ids = explode(',', $request->getParam('ids', false));
        if (empty($ids)) {
            throw new Jmango360_Japi_Exception(
                Mage::helper('japi')->__('Data invalid.'),
                Jmango360_Japi_Model_Request::HTTP_BAD_REQUEST
            );
        }

        /* @var $helper Jmango360_Japi_Helper_Product */
        $helper = Mage::helper('japi/product');

        /* @var $productCollection Mage_Catalog_Model_Resource_Product_Collection */
        $productCollection = Mage::getResourceModel('catalog/product_collection')
            ->addIdFilter($ids)
            ->setStoreId(Mage::app()->getStore()->getId())
            ->addAttributeToSelect(Mage::getSingleton('catalog/config')->getProductAttributes())
            ->addMinimalPrice()
            ->addFinalPrice()
            ->addTaxPercents()
            ->addUrlRewrite();

        Mage::getSingleton('catalog/product_status')->addVisibleFilterToCollection($productCollection);
        Mage::getSingleton('catalog/product_visibility')->addVisibleInCatalogFilterToCollection($productCollection);
        Mage::helper('japi/product')->applyHideOnAppFilter($productCollection);

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
            $data['message'] = Mage::helper('japi')->__('No products found.');
        }

        $order = $request->getParam('order');
        $dir = $request->getParam('dir');
        if ($order && $dir) {
            $productCollection->setOrder($order, $dir);
        }

        $helper->addPageSettings($productCollection);

        $productCollection->clear();
        $data['products'] = $helper->convertProductCollectionToApiResponseV2($productCollection);

        return $data;
    }
}