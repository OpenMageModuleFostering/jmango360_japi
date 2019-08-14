<?php

/**
 * Class Jmango360_Japi_Model_System_Config_Source_Catalog_Direction
 */
class Jmango360_Japi_Model_System_Config_Source_Catalog_Direction
{
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return array(
            array('value' => '', 'label' => Mage::helper('japi')->__('')),
            array('value' => 'asc', 'label' => Mage::helper('japi')->__('Ascending')),
            array('value' => 'desc', 'label' => Mage::helper('japi')->__('Descending'))
        );
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        return array(
            '' => Mage::helper('japi')->__(''),
            'asc' => Mage::helper('japi')->__('Ascending'),
            'desc' => Mage::helper('japi')->__('Descending')
        );
    }
}
