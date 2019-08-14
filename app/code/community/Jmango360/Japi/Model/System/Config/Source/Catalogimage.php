<?php

/**
 * Class Jmango360_Japi_Model_System_Config_Source_Catalogimage
 */
class Jmango360_Japi_Model_System_Config_Source_Catalogimage
{
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return array(
            array('value' => 'none', 'label' => Mage::helper('japi')->__('None')),
            array('value' => 'image', 'label' => Mage::helper('japi')->__('Base Image')),
            array('value' => 'small_image', 'label' => Mage::helper('japi')->__('Small Image')),
            array('value' => 'thumbnail', 'label' => Mage::helper('japi')->__('Thumbnail')),
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
            'none' => Mage::helper('japi')->__('None'),
            'image' => Mage::helper('japi')->__('Base Image'),
            'small_image' => Mage::helper('japi')->__('Small Image'),
            'thumbnail' => Mage::helper('japi')->__('Thumbnail'),
        );
    }
}
