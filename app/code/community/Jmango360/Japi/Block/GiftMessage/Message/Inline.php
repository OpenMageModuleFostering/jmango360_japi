<?php

class Jmango360_Japi_Block_GiftMessage_Message_Inline extends Mage_GiftMessage_Block_Message_Inline
{
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('japi/giftmessage/inline.phtml');
    }

    /**
     * Retrieve additional url
     *
     * @return bool
     */
    public function getAdditionalUrl()
    {
        return $this->getUrl('japi/checkout/getAdditional', array('_secure' => true));
    }
}