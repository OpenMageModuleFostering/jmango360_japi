<?php

class Jmango360_Japi_Block_Catalog_Layer_Filter_Category extends Mage_Catalog_Block_Layer_Filter_Category
{
    public function __construct()
    {
        parent::__construct();
        $this->_filterModelName = 'Mage_Catalog_Model_Layer_Filter_Category';
    }
}
