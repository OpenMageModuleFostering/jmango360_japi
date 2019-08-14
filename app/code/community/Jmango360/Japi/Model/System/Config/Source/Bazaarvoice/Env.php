<?php

class Jmango360_Japi_Model_System_Config_Source_Bazaarvoice_Env
{
    public function toOptionArray()
    {
        return array(
            'staging' => Mage::helper('japi')->__('Staging'),
            'production' => Mage::helper('japi')->__('Production')
        );
    }
}