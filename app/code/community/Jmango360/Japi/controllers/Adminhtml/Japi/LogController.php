<?php

/**
 * Copyright 2015 JMango360
 */
class Jmango360_Japi_Adminhtml_Japi_LogController extends Mage_Adminhtml_Controller_Action
{
    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('japi/log');
    }

    public function downloadAction()
    {
        $logFile = $this->getRequest()->getParam('file', 'japi.log');
        /* @var $helper Jmango360_Japi_Helper_Debug */
        $helper = Mage::helper('japi/debug');
        $logFilePath = $helper->getLogFile($logFile, true);
        if (!file_exists($logFilePath)) {
            return $this->_redirectUrl($_SERVER['HTTP_REFERER']);
        }
        $logSize = filesize($logFilePath);

        $this->getResponse()
            ->setHeader('Content-Type', 'application/force-download', true)
            ->setHeader('Content-Length', $logSize)
            ->setHeader('Content-Disposition', 'attachment;filename=' . $logFile)
            ->setBody(file_get_contents($logFilePath));
    }

    public function clearAction()
    {
        $logFile = $this->getRequest()->getParam('file');
        /* @var $helper Jmango360_Japi_Helper_Debug */
        $helper = Mage::helper('japi/debug');
        $logFilePath = $helper->getLogFile($logFile, true);
        if (file_exists($logFilePath)) {
            @unlink($logFilePath);
        }
        return $this->_redirectUrl($_SERVER['HTTP_REFERER']);
    }
}
