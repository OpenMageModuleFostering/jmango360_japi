<?php

/**
 * Copyright 2017 JMango360
 */
class Jmango360_Japi_Block_Payment_Info extends Mage_Payment_Block_Info
{
    /**
     * Prepare information specific to current payment method
     *
     * @param Varien_Object|array $transport
     * @return Varien_Object
     */
    protected function _prepareSpecificInformation($transport = null)
    {
        $transport = parent::_prepareSpecificInformation($transport);

        $paymentInfo = $this->getInfo();
        $data = (array)$paymentInfo->getAdditionalInformation();
        foreach ($data as $key => $value) {
            if ($key != Jmango360_Japi_Model_Payment::PAYMENT_ID && is_string($value)) {
                $transport->setData($this->_convertLabel($key), $value);
            }
        }

        return $transport;
    }

    /**
     * Convert payment information label to better readable
     *
     * @param $label
     * @return string
     */
    protected function _convertLabel($label)
    {
        return ucwords(str_replace('_', ' ', $label));
    }
}
