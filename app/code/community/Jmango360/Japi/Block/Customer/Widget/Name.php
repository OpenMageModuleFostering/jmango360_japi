<?php

/**
 * Copyright 2017 JMango360
 */
class Jmango360_Japi_Block_Customer_Widget_Name extends Mage_Customer_Block_Widget_Name
{
    protected $_atttributeMap = array(
        'firstname' => 'First Name',
        'lastname' => 'Last Name'
    );

    /**
     * Retrieve store attribute label
     *
     * @param string $attributeCode
     * @return string
     */
    public function getStoreLabel($attributeCode)
    {
        try {
            $attribute = $this->_getAttribute($attributeCode);
            return $attribute ? $this->__($attribute->getStoreLabel()) : '';
        } catch (Exception $e) {
            if (isset($this->_atttributeMap[$attributeCode])) {
                return $this->__($this->_atttributeMap[$attributeCode]);
            }
        }
    }
}
