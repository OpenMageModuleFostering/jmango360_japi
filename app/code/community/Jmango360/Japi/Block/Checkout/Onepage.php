<?php

/**
 * Copyright 2015 JMango360
 */
if (@class_exists('Kega_Checkout_Block_Onepage')) {
    class Jmango360_Japi_Block_Checkout_Onepage_Abstract extends Kega_Checkout_Block_Onepage
    {
    }
} elseif (Mage::helper('core')->isModuleEnabled('LaPoste_SoColissimoSimplicite') && @class_exists('LaPoste_SoColissimoSimplicite_Block_Onepage')
) {
    class Jmango360_Japi_Block_Checkout_Onepage_Abstract extends LaPoste_SoColissimoSimplicite_Block_Onepage
    {
    }
} elseif (@class_exists('Flagbit_Checkout_Block_Onepage')) {
    class Jmango360_Japi_Block_Checkout_Onepage_Abstract extends Flagbit_Checkout_Block_Onepage
    {
    }
} else {
    class Jmango360_Japi_Block_Checkout_Onepage_Abstract extends Mage_Checkout_Block_Onepage
    {
    }
}

class Jmango360_Japi_Block_Checkout_Onepage extends Jmango360_Japi_Block_Checkout_Onepage_Abstract
{
    public function getSteps()
    {
        $steps = parent::getSteps();

        if (Mage::helper('core')->isModuleEnabled('Flagbit_Checkout')) {
            foreach ($steps as $code => $step) {
                switch ($code) {
                    case 'billing':
                        $steps[$code]['label'] = $this->__('Personal Information');
                        break;
                    case 'payment':
                        $steps[$code]['label'] = $this->__('Payment &amp; Shipping');
                        break;
                    case 'review':
                        $steps[$code]['label'] = $this->__('Order confirmation');

                }
            }
        }

        return $steps;
    }

    /**
     * Get checkout steps codes
     *
     * @return array
     */
    protected function _getStepCodes()
    {
        if (Mage::helper('core')->isModuleEnabled('MadeByMouses_Olifant')) {
            $isNotB2B = Mage::app()->getStore()->getCode() != 'wholesale';
            if(Mage::helper('customer')->isLoggedIn() && $isNotB2B){
                return array('login', 'billing', 'shipping', 'olifant', 'shipping_method', 'payment', 'review');
            }
        }
        return parent::_getStepCodes();
    }
}