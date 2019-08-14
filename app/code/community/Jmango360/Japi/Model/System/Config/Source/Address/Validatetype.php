<?php

/**
 * Class Jmango360_Japi_Model_System_Config_Source_Address_Validatetype
 */
class Jmango360_Japi_Model_System_Config_Source_Address_Validatetype
{
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return array(
            array('value' => 'any', 'label'=>Mage::helper('japi')->__('Any')),
            array('value' => 'number', 'label'=>Mage::helper('japi')->__('Number'))
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
            'any' => Mage::helper('japi')->__('Any'),
            'number' => Mage::helper('japi')->__('Number')
        );
    }
}