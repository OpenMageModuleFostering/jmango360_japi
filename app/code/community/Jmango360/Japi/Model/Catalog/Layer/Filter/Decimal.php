<?php

class Jmango360_Japi_Model_Catalog_Layer_Filter_Decimal extends Mage_Catalog_Model_Layer_Filter_Decimal
{
    /**
     * Retrieve resource instance
     *
     * @return Jmango360_Japi_Model_Resource_Catalog_Layer_Filter_Decimal
     */
    protected function _getResource()
    {
        if (is_null($this->_resource)) {
            $this->_resource = Mage::getResourceModel('japi/catalog_layer_filter_decimal');
        }
        return $this->_resource;
    }
}