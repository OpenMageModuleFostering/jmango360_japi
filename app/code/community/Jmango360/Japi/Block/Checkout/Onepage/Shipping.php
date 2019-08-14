<?php

/**
 * Copyright 2016 JMango360
 */
class Jmango360_Japi_Block_Checkout_Onepage_Shipping extends Mage_Checkout_Block_Onepage_Shipping
{
    public function getAddress()
    {
        if (is_null($this->_address)) {
            $this->_address = $this->getQuote()->getShippingAddress();
        }

        return $this->_address;
    }
}