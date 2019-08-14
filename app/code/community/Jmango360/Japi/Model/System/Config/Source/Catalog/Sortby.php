<?php

/**
 * Class Jmango360_Japi_Model_System_Config_Source_Catalog_Sortby
 */
class Jmango360_Japi_Model_System_Config_Source_Catalog_Sortby
{
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        $options = array(array(
            'label' => Mage::helper('catalog')->__('Position'),
            'value' => 'position'
        ));

        /** @var Mage_Catalog_Model_Config $catalogConfig */
        $catalogConfig = Mage::getSingleton('catalog/config');
        foreach ($catalogConfig->getAttributesUsedForSortBy() as $attribute) {
            $options[] = array(
                'label' => Mage::helper('catalog')->__($attribute['frontend_label']),
                'value' => $attribute['attribute_code']
            );
        }

        return $options;
    }
}