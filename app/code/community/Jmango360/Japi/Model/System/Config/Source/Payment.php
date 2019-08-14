<?php

class Jmango360_Japi_Model_System_Config_Source_Payment
{
    public function toOptionArray()
    {
        /* @var $shippingConfig Mage_Payment_Model_Config */
        $shippingConfig = Mage::getSingleton('payment/config');
        $methods = $shippingConfig->getActiveMethods();
        $options = array();

        foreach ($methods as $code => $method) {
            $title = Mage::getStoreConfig("payment/$code/title");
            $options[] = array('value' => $code, 'label' => $title ? $title : $code);
        }

        return $options;
    }
}