<?php

class Jmango360_Japi_Block_Catalog_Layer_View extends Mage_Catalog_Block_Layer_View
{
    /**
     * Initialize blocks names
     */
    protected function _initBlocks()
    {
        $this->_stateBlockName = 'catalog/layer_state';
        $this->_categoryBlockName = 'catalog/layer_filter_category';
        $this->_attributeFilterBlockName = 'Jmango360_Japi_Block_Catalog_Layer_Filter_Attribute';
        $this->_priceFilterBlockName = 'Jmango360_Japi_Block_Catalog_Layer_Filter_Price';
        $this->_decimalFilterBlockName = 'Jmango360_Japi_Block_Catalog_Layer_Filter_Decimal';
    }

    /**
     * Get layer object
     *
     * @return Mage_Catalog_Model_Layer
     */
    public function getLayer()
    {
        return Mage::getSingleton('Mage_Catalog_Model_Layer');
    }
}