<?php

class Jmango360_Japi_Model_System_Config_Source_Shipping
{
    public function toOptionArray()
    {
        /* @var $shippingConfig Mage_Shipping_Model_Config */
        $shippingConfig = Mage::getSingleton('shipping/config');
        $carriers = $shippingConfig->getActiveCarriers();
        $options = array();

        foreach ($carriers as $carrierCode => $carrier) {
            /* @var $carrier Mage_Shipping_Model_Carrier_Abstract */
            $title = Mage::getStoreConfig("carriers/$carrierCode/title");
            $group = array();
            $methods = $carrier->getAllowedMethods();
            if (!empty($methods)) {
                foreach ($carrier->getAllowedMethods() as $methodCode => $method) {
                    $group[] = array('value' => $carrierCode . '_' . $methodCode, 'label' => $method);
                }
            } else {
                $group[] = array('value' => $carrierCode, 'label' => $carrierCode);
            }
            $options[] = array('value' => !empty($group) ? $group : $carrierCode, 'label' => $title ? $title : $carrierCode);
        }

        return $options;
    }
}