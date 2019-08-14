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
            $data['toolbar_info'] = $helper->getToolBarInfo($productCollection);
            /**
             * Add group by product's ID to collection
             */
            if (version_compare(Mage::getVersion(), '1.9.2.1', '>')) {
                $productCollection->getSelect()->group('e.entity_id');
            }

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
}