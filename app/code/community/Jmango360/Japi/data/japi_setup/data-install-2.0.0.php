<?php
$config = Mage::getConfig();
/* @var $helper Mage_Core_Helper_Data */
$helper = Mage::helper('core');
$apiKey = strtoupper(substr($helper->uniqHash(), 0, 16));
$config->saveConfig('japi/jmango_rest_api/apikey', $apiKey);
$config->saveConfig('japi/jmango_rest_api/apiuser', 'jmango360');