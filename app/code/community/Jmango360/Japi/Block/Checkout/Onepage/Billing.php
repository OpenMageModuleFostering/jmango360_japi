<?php

/**
 * Copyright 2016 JMango360
 */
if (Mage::helper('core')->isModuleEnabled('Nedstars_Checkout') && @class_exists('Nedstars_Checkout_Block_Onepage_Billing')) {
    class Jmango360_Japi_Block_Checkout_Onepage_Billing_Abstract extends Nedstars_Checkout_Block_Onepage_Billing
    {
    }
} elseif (@class_exists('Flagbit_Checkout_Block_Onepage_Billing')) {
    class Jmango360_Japi_Block_Checkout_Onepage_Billing_Abstract extends Flagbit_Checkout_Block_Onepage_Billing
    {
    }
} else {
    class Jmango360_Japi_Block_Checkout_Onepage_Billing_Abstract extends Mage_Checkout_Block_Onepage_Billing
    {
    }
}

class Jmango360_Japi_Block_Checkout_Onepage_Billing extends Jmango360_Japi_Block_Checkout_Onepage_Billing_Abstract
{
    public function __construct()
    {
        if (Mage::helper('core')->isModuleEnabled('Massamarkt_Core')) {
            $this->setTemplate('japi/checkout/onepage/massamarkt/billing.phtml');
        }
        parent::__construct();
    }

    public function getAddress()
    {
        if (is_null($this->_address)) {
            $this->_address = $this->getQuote()->getBillingAddress();

            if ($this->isCustomerLoggedIn()) {
                $this->_address = $this->getQuote()->getBillingAddress();
                if (!$this->_address->getFirstname()) {
                    $this->_address->setFirstname($this->getQuote()->getCustomer()->getFirstname());
                }
                if (!$this->_address->getLastname()) {
                    $this->_address->setLastname($this->getQuote()->getCustomer()->getLastname());
                }
            }
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