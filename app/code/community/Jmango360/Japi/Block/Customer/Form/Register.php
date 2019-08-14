<?php

class Jmango360_Japi_Block_Customer_Form_Register extends Mage_Customer_Block_Form_Register
{
    /**
     * Retrieve success url
     */
    public function getSuccessUrl()
    {
        return $this->getUrl('*/*/register', array('_secure' => true));
    }

    /**
     * Retrieve error url
     */
    public function getErrorUrl()
    {
        return $this->getUrl('*/*/register', array('_secure' => true));
    }

    /**
     * Retrieve form posting url
     *
     * @return string
     */
    public function getPostActionUrl()
    {
        return $this->getUrl('*/*/createPost', array('_secure' => true));
    }
}
