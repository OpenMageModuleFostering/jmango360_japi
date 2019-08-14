<?php

class Jmango360_Japi_Block_Adminhtml_System_Config_Form_Button extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    /**
     * @param Varien_Data_Form_Element_Abstract $element
     * @return string
     */
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        return $this->getLayout()->createBlock('adminhtml/widget_button', '', array(
            'type' => 'button',
            'label' => $this->helper('japi')->__('Re-run'),
            'onclick' => sprintf('confirmSetLocation(\'%s\', \'%s\')',
                $this->__('Are you sure?'), $this->getUrl('adminhtml/japi_troubleshooting/sql')
            )
        ))->toHtml();
    }
}