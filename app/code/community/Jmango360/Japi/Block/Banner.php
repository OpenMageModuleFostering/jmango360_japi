<?php

/**
 * Copyright 2016 JMango360
 */
class Jmango360_Japi_Block_Banner extends Mage_Core_Block_Template
{
    public function getIcon($icon = null)
    {
        if (!$icon) return '';
        return Mage::getBaseUrl('media') . 'japi/icon/' . Mage::getStoreConfig('japi/jmango_smart_app_banner/' . $icon);
    }
}
