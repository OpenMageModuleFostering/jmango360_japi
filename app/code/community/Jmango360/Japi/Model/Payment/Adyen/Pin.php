<?php

/**
 * Copyright 2017 JMango360
 */
class Jmango360_Japi_Model_Payment_Adyen_Pin extends Jmango360_Japi_Model_Payment
{
    protected $_code = 'jmango_payment_adyen_pin';

    /**
     * Validate payment transaction by mobile app
     *
     * @return $this
     */
    public function validate()
    {
        return $this;
    }
}
