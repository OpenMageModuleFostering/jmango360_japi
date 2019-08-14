<?php

/**
 * Copyright 2016 JMango360
 */
class Jmango360_Japi_Block_Banner extends Mage_Core_Block_Template
{
    public function getIcon($icon = null)
    {
        if (!$icon) return '';
        $iconUrl = Mage::getStoreConfig('japi/jmango_smart_app_banner/' . $icon);
        return strpos($iconUrl, 'http') === 0 ? $iconUrl : Mage::getBaseUrl('media') . 'japi/icon/' . $iconUrl;
    }
}
