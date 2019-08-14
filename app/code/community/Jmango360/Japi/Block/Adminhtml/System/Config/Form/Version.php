<?php

class Jmango360_Japi_Block_Adminhtml_System_Config_Form_Version extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    /**
     * @param Varien_Data_Form_Element_Abstract $element
     * @return string
     */
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        $class = get_class($this);
        $parts = explode('_', $class);
        $module = ucfirst($parts[0]) . '_' . ucfirst($parts[1]);

        return (string)Mage::getConfig()->getNode('modules')->$module->version;
    }
}