<?php

/**
 * Copyright 2015 JMango360
 */
class Jmango360_Japi_Adminhtml_Japi_TroubleshootingController extends Mage_Adminhtml_Controller_Action
{
    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('japi/troubleshooting');
    }

    public function sqlAction()
    {
        /* @var $resource Mage_Core_Model_Resource */
        $resource = Mage::getSingleton('core/resource');
        $writeConnection = $resource->getConnection('core_write');
        $coreResourceTable = $resource->getTableName('core/resource');

        try {
            $query = "DELETE FROM {$coreResourceTable} WHERE code = 'japi_setup'";
            Mage::app()->cleanCache(array(Mage_Core_Model_Config::CACHE_TAG));
            $writeConnection->query($query);
            $this->_getSession()->addSuccess($this->__('Re-run successfully.'));
        } catch (Exception $e) {
            $this->_getSession()->addError($e->getMessage());
        }

        return $this->_redirectUrl($this->_getRefererUrl());
    }
}
