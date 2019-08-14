<?php

/**
 * Copyright 2015 JMango360
 */
class Jmango360_Japi_Model_Magpleasure_Tierprices_Price extends Magpleasure_Tierprices_Model_Price
{
    protected function _isCheckout()
    {
        if (Mage::app()->getRequest()->getModuleName() == 'japi') {
            return true;
        } else {
            return parent::_isCheckout();
        }
    }
}