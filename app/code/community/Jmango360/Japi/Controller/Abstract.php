<?php

class Jmango360_Japi_Controller_Abstract extends Mage_Core_Controller_Front_Action
{
    /* @var Jmango360_Japi_Model_Server */
    protected $_server = null;

    public function _construct()
    {
        if (!Mage::getStoreConfigFlag('japi/jmango_rest_developer_settings/enable')) {
            // suppress notice, warning
            error_reporting(E_ERROR | E_PARSE);
        }

        $this->_server = Mage::getSingleton('japi/server');
        $this->_server->setControllerInstance($this);

        /* @var $helper Jmango360_Japi_Helper_Data */
        $helper = Mage::helper('japi');
        //Set Theme follow by Design config of Magento
        $helper->setTheme(
            Mage::getSingleton('core/design_package')->getTheme('frontend'),
            Mage::getSingleton('core/design_package')->getPackageName()
        );
    }

    /**
     * @return Jmango360_Japi_Model_Server
     */
    public function getServer()
    {
        return $this->_server;
    }
}