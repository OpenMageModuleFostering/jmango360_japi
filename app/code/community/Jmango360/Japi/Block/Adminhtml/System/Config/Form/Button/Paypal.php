<?php

/**
 * Copyright 2015 JMango360
 */
class Jmango360_Japi_Block_Adminhtml_System_Config_Form_Button_Paypal extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    protected $_element;

    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('japi/system/config/form/button/paypal.phtml');
    }

    /**
     * Return element html
     *
     * @param  Varien_Data_Form_Element_Abstract $element
     * @return string
     */
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        $this->_element = $element;
        return $this->_toHtml();
    }

    public function getButtonHtml()
    {
        return $this->getLayout()->createBlock('adminhtml/widget_button', '', array(
            'type' => 'button',
            'label' => $this->helper('japi')->__('Test API Credentials'),
            'onclick' => sprintf('japiPaypalTestAPICredentials()')
        ))->toHtml();
    }
}
