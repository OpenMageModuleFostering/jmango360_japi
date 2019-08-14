<?php

class Jmango360_Japi_Block_Checkout_Onepage_Olifant extends Mage_Checkout_Block_Onepage_Abstract {
    protected function _construct()
    {
        if (Mage::helper('core')->isModuleEnabled('MadeByMouses_Olifant')) {
            $this->getCheckout()->setStepData('olifant', array(
                'label'     => Mage::helper('olifant')->__('SPAAROLIFANTEN'),
                'is_show'   => true
            ));
            $this->setTemplate('olifant/onepage-step.phtml');
        }
        parent::_construct();
    }
}