<?php

/**
 * Copyright 2016 JMango360
 */
class Jmango360_Japi_Block_Checkout_Onepage_Addjs extends Mage_Page_Block_Html_Head
{
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('japi/checkout/onepage/js.phtml');
    }

    public function getCustomCss()
    {
        $css = Mage::getStoreConfig('japi/jmango_rest_checkout_settings/custom_css');
        if ($css) return $css;
    }
}