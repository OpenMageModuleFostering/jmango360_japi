<?php

class Jmango360_Japi_Block_Catalog_Product_List extends Mage_Catalog_Block_Product_List
{
    /**
     * Get catalog layer model
     * Fix for some conflict modules
     */
    public function getLayer()
    {
        $layer = Mage::registry('current_layer');
        if ($layer) {
            return $layer;
        }
        $layer = Mage::getSingleton('catalog/layer');
        switch (get_class($layer)) {
            case 'RicardoMartins_OutofstockLast_Model_Catalog_Layer':
                return $layer;
                break;
        }
        return Mage::getSingleton('Mage_Catalog_Model_Layer');
    }
}