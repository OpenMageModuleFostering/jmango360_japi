<?php

class Jmango360_Japi_Block_Catalogsearch_Layer extends Mage_CatalogSearch_Block_Layer
{
    /**
     * Initialize blocks names
     */
    protected function _initBlocks()
    {
        parent::_initBlocks();
        $this->_stateBlockName = 'Mage_Catalog_Block_Layer_State';
        $this->_categoryBlockName = 'Jmango360_Japi_Block_Catalog_Layer_Filter_Category';
        $this->_attributeFilterBlockName = 'Jmango360_Japi_Block_Catalogsearch_Layer_Filter_Attribute';
        $this->_priceFilterBlockName = 'Jmango360_Japi_Block_Catalog_Layer_Filter_Price';
        $this->_decimalFilterBlockName = 'Jmango360_Japi_Block_Catalog_Layer_Filter_Decimal';
    }

    /**
     * Get layer object
     * Also fix for some conflict modules
     */
    public function getLayer()
    {
        $layer = Mage::getSingleton('catalogsearch/layer');
        switch (get_class($layer)) {
            case 'Mage_CatalogSearch_Model_Layer':
            case 'Mirasvit_SearchIndex_Model_Catalogsearch_Layer':
            case 'Flagbit_FactFinder_Model_Layer':
            case 'RicardoMartins_OutofstockLast_Model_CatalogSearch_Layer':
                return $layer;
                break;
            default:
                return Mage::getSingleton('Mage_CatalogSearch_Model_Layer');
        }
    }
}