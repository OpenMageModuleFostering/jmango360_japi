<?php

/**
 * Copyright 2016 JMango360
 */
class Jmango360_Japi_Block_Checkout_Onepage_Address extends Mage_Core_Block_Template
{
    protected $_address;
    protected $_prefix;

    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('japi/checkout/onepage/address.phtml');
    }

    public function getFields()
    {
        /* @var $model Jmango360_Japi_Model_Rest_Mage */
        $model = Mage::getModel('japi/rest_mage');
        $fields = $model->getAddressAttributes();

        return $fields;
    }

    public function getAddress()
    {
        return $this->_address;
    }

    public function setAddress($address)
    {
        $this->_address = $address;
        return $this;
    }

    public function getFieldPrefix()
    {
        return $this->_prefix;
    }

    public function setFieldPrefix($prefix)
    {
        $this->_prefix = $prefix;
        return $this;
    }

    public function getFieldName($field)
    {
        return sprintf('%s[%s]', $this->_prefix, $field);
    }

    public function getFieldId($field)
    {
        return sprintf('%s:%s', $this->_prefix, $field);
    }

    public function getFieldValue($field)
    {
        return $this->getAddress()->getData($field);
    }
}