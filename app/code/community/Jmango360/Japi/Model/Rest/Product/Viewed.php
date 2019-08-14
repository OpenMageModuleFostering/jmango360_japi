<?php

class Jmango360_Japi_Model_Rest_Product_Viewed extends Jmango360_Japi_Model_Rest_Product
{
    /**
     * Get list recently viewed products
     * @return array
     */
    public function getList()
    {
        $data = array();

        $block = $this->_getViewedBlock();

        /* @var $_collection Mage_Reports_Model_Resource_Product_Index_Collection_Abstract */
        $_collection = $block->getItemsCollection();

        if (!$_collection->getSize()) {
            $data['message'] = Mage::helper('japi')->__('No products found.');
        }

        foreach ($_collection as $item) {
            /* @var $item Mage_Catalog_Model_Product */
            if ($item->getId()) {
                $ids[] = $item->getId();
            }
        }

        if (!empty($ids)) {
            /* @var $helper Jmango360_Japi_Helper_Product */
            $helper = Mage::helper('japi/product');
            $data['products'][] = $helper->convertProductIdsToApiResponse($ids);
        }

        return $data;
    }

    /**
     * @return Mage_Reports_Block_Product_Viewed
     */
    protected function _getViewedBlock()
    {
        return Mage::app()->getLayout()->createBlock('Mage_Reports_Block_Product_Viewed');
    }
}