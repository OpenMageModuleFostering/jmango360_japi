<?php

class Jmango360_Japi_Block_Catalogsearch_Layer_Filter_Attribute extends Mage_CatalogSearch_Block_Layer_Filter_Attribute
{
    /**
     * Set filter model name
     */
    public function __construct()
    {
        parent::__construct();
        $this->_filterModelName = 'Jmango360_Japi_Model_Catalogsearch_Layer_Filter_Attribute';
    }
}