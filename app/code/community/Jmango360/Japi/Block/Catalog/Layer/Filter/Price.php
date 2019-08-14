<?php

class Jmango360_Japi_Block_Catalog_Layer_Filter_Price extends Mage_Catalog_Block_Layer_Filter_Price
{
    public function __construct()
    {
        parent::__construct();
        $this->_filterModelName = 'Jmango360_Japi_Model_Catalog_Layer_Filter_Price';
    }
}