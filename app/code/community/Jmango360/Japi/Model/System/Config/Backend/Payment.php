<?php

class Jmango360_Japi_Model_System_Config_Backend_Payment extends Mage_Core_Model_Config_Data
{
    protected function _beforeSave()
    {
        $value = $this->getValue();
        $source = new Jmango360_Japi_Model_System_Config_Source_Payment();
        $options = $source->toOptionArray();
        $origin = array();

        foreach ($options as $option) {
            if (!is_array($option['value'])) {
                $origin[] = $option['value'];
            } else {
                foreach ($option['value'] as $item) {
                    $origin[] = $item['value'];
                }
            }
        }

        if (is_array($value) && count($value) == count($origin)) {
            Mage::throwException(Mage::helper('japi')->__('You must leave at least one payment method available.'));
        }

        return $this;
    }
}