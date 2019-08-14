<?php

class Jmango360_Japi_Helper_debug extends Mage_Core_Helper_Abstract
{
    public function getLogFile($logFile = 'japi.log', $include_path = false)
    {
        if (file_exists($logFile)) { // should not a absolute path
            return '';
        }

        if (strpos($logFile, '/') === 0 || strpos($logFile, '..') === 0) { // should not a relative path
            return '';
        }

        if ($include_path) {
            return Mage::getBaseDir('var') . DS . 'log' . DS . $logFile;
        } else {
            return $logFile;
        }
    }
}
