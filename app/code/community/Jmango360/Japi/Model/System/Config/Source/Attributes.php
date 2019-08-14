<?php

class Jmango360_Japi_Model_System_Config_Source_Attributes
{
    public function toOptionArray()
    {
        /* @var $collection Mage_Catalog_Model_Resource_Product_Attribute_Collection */
        $collection = Mage::getResourceModel('catalog/product_attribute_collection')
            ->addVisibleFilter();

        $options = array(
            array('value' => '', 'label' => '')
        );
        foreach ($collection as $attribute) {
            /* @var $attribute Mage_Catalog_Model_Resource_Eav_Attribute */
            $options[] = array(
                'value' => $attribute->getAttributeCode(),
                'label' => sprintf('%s [%s]', $attribute->getFrontendLabel(), $attribute->getAttributeCode())
            );
        }

        return $options;
    }
}