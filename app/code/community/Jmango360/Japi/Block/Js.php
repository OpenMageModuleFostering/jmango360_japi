<?php

/**
 * Copyright 2017 JMango360
 */
class Jmango360_Japi_Block_Js extends Mage_Core_Block_Template
{
    protected $orderId;

    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('japi/js.phtml');
    }

    public function isShow()
    {
        /* @var $httpHelper Mage_Core_Helper_Http */
        $httpHelper = Mage::helper('core/http');
        return strpos($httpHelper->getHttpUserAgent(), 'JM360-Mobile') === false && Mage::app()->getRequest()->getModuleName() != 'japi';
    }

    public function setOrderId($orderId)
    {
        $this->orderId = $orderId;
        return $this;
    }

    public function getOrderId()
    {
        return $this->orderId;
    }

    /**
     * @param $uri string
     * @param $orderID string
     * @return string
     */
    public function getUriScheme($uri, $orderID = null)
    {
        if (!$uri) return '';
        $uri = trim($uri);
        if (!$orderID) $orderID = $this->getOrderId();

        if (strpos($uri, 'intent://') === 0) {
            return str_replace(';end', sprintf(';S.orderId=%s;end', $orderID), $uri);
        } else {
            return strpos($uri, '?') !== false
                ? sprintf('%s&transasction_id=%s', $uri, $orderID)
                : sprintf('%s?transasction_id=%s', $uri, $orderID);
        }
    }
}
