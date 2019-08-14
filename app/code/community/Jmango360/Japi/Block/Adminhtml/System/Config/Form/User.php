<?php

/**
 * Copyright 2015 JMango360
 */
class Jmango360_Japi_Block_Adminhtml_System_Config_Form_User extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    /**
     * Return element html
     *
     * @param  Varien_Data_Form_Element_Abstract $element
     * @return string
     */
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        return Mage::getStoreConfig('japi/jmango_rest_api/apiuser');
    }
}
