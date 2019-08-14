<?php

/**
 * Copyright 2016 JMango360
 */
class Jmango360_Japi_Block_Form extends Mage_Core_Block_Template
{
    protected $_fields;
    protected $_prefix;
    protected $_form;

    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('japi/form.phtml');
    }

    public function setForm($form)
    {
        $this->_form = $form;
        return $this;
    }

    public function getForm()
    {
        return $this->_form;
    }

    public function getFields()
    {
        if ($this->_fields) return $this->_fields;
        switch ($this->_form) {
            case 'billing':
                if (Mage::helper('core')->isModuleEnabled('Amasty_Customerattr')
                    && Mage::getStoreConfig('amcustomerattr/general/front_auto_output')
                ) {
                    Mage::app()->getRequest()->setModuleName('checkout');
                    return array();
                } else {
                    $fields = array_merge(
                        Mage::helper('japi')->getCustomerAddressFormFields(),
                        Mage::helper('japi')->getCheckoutAddressFormFields()
                    );
                }
                $keys = array();
                foreach ($fields as $i => $field) {
                    if (!in_array($field['key'], $keys)) {
                        $keys[] = $field['key'];
                    } else {
                        unset($fields[$i]);
                    }
                }
                return $fields;
                break;
        }
        return array();
    }

    public function setFields($fields)
    {
        $this->_fields = $fields;
        return $this;
    }

    public function getPrefix()
    {
        return $this->_prefix;
    }

    public function setPrefix($prefix)
    {
        $this->_prefix = $prefix;
        return $this;
    }

    public function getFieldName($field)
    {
        return $this->_prefix ? sprintf('%s[%s]', $this->_prefix, $field) : $field;
    }

    public function getFieldId($field)
    {
        return $this->_prefix ? sprintf('%s:%s', $this->_prefix, $field) : $field;
    }

    public function getFieldValue($field)
    {

    }
}