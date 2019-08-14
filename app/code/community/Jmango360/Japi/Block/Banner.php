<?php

/**
 * Copyright 2016 JMango360
 */
class Jmango360_Japi_Block_Banner extends Mage_Core_Block_Template
{
    /**
     * Check to show banner
     *
     * @return bool
     */
    public function isShow()
    {
        $request = Mage::app()->getRequest();
        if ($request->getModuleName() == 'japi') return false;

        /* @var $server Jmango360_Japi_Model_Server */
        $server = Mage::getSingleton('japi/server');
        if ($server->getIsRest()) return false;

        if (!Mage::getStoreConfigFlag('japi/jmango_smart_app_banner/enable')) return false;

        /* @var $httpHelper Mage_Core_Helper_Http */
        $httpHelper = Mage::helper('core/http');
        if (strpos($httpHelper->getHttpUserAgent(), 'JM360-Mobile') !== false) return false;

        return true;
    }

    /**
     * Get app icon base on OS
     *
     * @param null $icon
     * @return mixed|string
     */
    public function getIcon($icon = null)
    {
        if (!$icon) return '';
        $iconUrl = Mage::getStoreConfig('japi/jmango_smart_app_banner/' . $icon);
        return strpos($iconUrl, 'http') === 0 ? $iconUrl : Mage::getBaseUrl('media') . 'japi/icon/' . $iconUrl;
    }
}
