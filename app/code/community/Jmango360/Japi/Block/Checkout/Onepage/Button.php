<?php

/**
 * Copyright 2016 JMango360
 */
if (@class_exists('Iways_PayPalPlus_Block_Onepage_Review_Button')) {
    class Jmango360_Japi_Block_Checkout_Onepage_Button_Abstract extends Iways_PayPalPlus_Block_Onepage_Review_Button
    {
    }
} else {
    class Jmango360_Japi_Block_Checkout_Onepage_Button_Abstract extends Mage_Core_Block_Template
    {
    }
}

class Jmango360_Japi_Block_Checkout_Onepage_Button extends Jmango360_Japi_Block_Checkout_Onepage_Button_Abstract
{
    protected function _construct()
    {
        parent::_construct();

        if (Mage::helper('core')->isModuleEnabled('Iways_PayPalPlus')) {
            $this->setTemplate('paypalplus/review/button.phtml');
        } else {
            $this->setTemplate('japi/checkout/onepage/js.phtml');
        }
    }
}