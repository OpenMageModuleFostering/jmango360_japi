<?php
$PATH_XML_API_USER = 'japi/jmango_rest_api/apiuser';
$PATH_XML_API_KEY = 'japi/jmango_rest_api/apikey';

$config = Mage::getConfig();
if (!Mage::getStoreConfig($PATH_XML_API_KEY)) {
    /* @var $helper Mage_Core_Helper_Data */
    $helper = Mage::helper('core');
    $apiKey = strtoupper(substr($helper->uniqHash(), 0, 16));
    $config->saveConfig($PATH_XML_API_KEY, $apiKey);
    $config->saveConfig($PATH_XML_API_USER, 'jmango360');
}
