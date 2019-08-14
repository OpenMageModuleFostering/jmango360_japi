<?php

/**
 * Copyright 2016 JMango360
 */
if (Mage::helper('core')->isModuleEnabled('Nedstars_Checkout') && @class_exists('Nedstars_Checkout_Block_Onepage_Shipping')) {
    class Jmango360_Japi_Block_Checkout_Onepage_Shipping_Abstract extends Nedstars_Checkout_Block_Onepage_Shipping
    {

    }
} else {
    class Jmango360_Japi_Block_Checkout_Onepage_Shipping_Abstract extends Mage_Checkout_Block_Onepage_Shipping
    {

    }
}

class Jmango360_Japi_Block_Checkout_Onepage_Shipping extends Jmango360_Japi_Block_Checkout_Onepage_Shipping_Abstract
{
    public function getAddress()
    {
        if (is_null($this->_address)) {
            $this->_address = $this->getQuote()->getShippingAddress();
        }

        return $this->_address;
    }

    public function getAddressesHtmlSelect($type)
    {
        $html = parent::getAddressesHtmlSelect($type);

        if ($html) {
            $editLink = sprintf('<div class="japi-address-edit"><a href="#" class="japi-address-edit-btn">%s</a></div>',
                $this->__('Edit')
            );

            $html .= $editLink;
        }

        return $html;
    }
}