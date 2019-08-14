<?php

/**
 * Class Jmango360_Japi_Model_System_Config_Source_Defaultimage
 */
class Jmango360_Japi_Model_System_Config_Source_Defaultimage
{

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return array(
            array('value' => 'image', 'label'=>Mage::helper('japi')->__('Base Image')),
            array('value' => 'small_image', 'label'=>Mage::helper('japi')->__('Small Image')),
            array('value' => 'thumbnail', 'label'=>Mage::helper('japi')->__('Thumbnail')),
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
            'image' => Mage::helper('japi')->__('Base Image'),
            'small_image' => Mage::helper('japi')->__('Small Image'),
            'thumbnail' => Mage::helper('japi')->__('Thumbnail'),
        );
    }

}
