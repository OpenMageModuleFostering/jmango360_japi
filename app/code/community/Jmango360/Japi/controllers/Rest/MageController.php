<?php

class Jmango360_Japi_Rest_MageController extends Jmango360_Japi_Controller_Abstract
{
    public function storeAction()
    {
        $server = $this->getServer();
        $server->setRestDispatchModel(Mage::getModel('japi/rest_mage'));
        $server->run();
    }

    public function getSessionAction()
    {
        $server = $this->getServer();
        $server->setRestDispatchModel(Mage::getModel('japi/rest_mage'));
        $server->run();
    }

    public function getTokenAction()
    {
        $server = $this->getServer();
        $server->setRestDispatchModel(Mage::getModel('japi/rest_mage'));
        $server->run();
    }

    public function getMagentoInfoAction()
    {
        $server = $this->getServer();
        $server->setRestDispatchModel(Mage::getModel('japi/rest_mage'));
        $server->run();
    }

    public function getPluginVersionAction()
    {
        $server = $this->getServer();
        $server->setRestDispatchModel(Mage::getModel('japi/rest_mage'));
        $server->run();
    }

    public function getConfigInfoAction()
    {
        $server = $this->getServer();
        $server->setRestDispatchModel(Mage::getModel('japi/rest_mage'));
        $server->run();
    }

    public function getDirectoryCountryListAction()
    {
        $server = $this->getServer();
        $server->setRestDispatchModel(Mage::getModel('japi/rest_mage'));
        $server->run();
    }

    public function getDirectoryRegionListAction()
    {
        $server = $this->getServer();
        $server->setRestDispatchModel(Mage::getModel('japi/rest_mage'));
        $server->run();
    }

    public function getMagentoModulesAction()
    {
        $server = $this->getServer();
        $server->setRestDispatchModel(Mage::getModel('japi/rest_mage'));
        $server->run();
    }

    public function getModuleRewritesAction()
    {
        $server = $this->getServer();
        $server->setRestDispatchModel(Mage::getModel('japi/rest_mage'));
        $server->run();
    }

    public function updateThemeAction()
    {
        $server = $this->getServer();
        $server->setRestDispatchModel(Mage::getModel('japi/rest_mage'));
        $server->run();
    }

    public function getThemeAction()
    {
        $server = $this->getServer();
        $server->setRestDispatchModel(Mage::getModel('japi/rest_mage'));
        $server->run();
    }

    public function redirectAction()
    {
        /* @var $helper Jmango360_Japi_Helper_Data */
        $helper = Mage::helper('japi');

        /**
         * MPLUGIN-1377: Reset PHPSESSID
         */
        $helper->checkValidSession();

        /**
         * MPLUGIN-1126: by pass check user's IP adress to auto redirect when website installed "Experius_Geoipredirect"
         */
        if ($helper->isModuleEnabled('Experius_Geoipredirect')) {
            Mage::getSingleton('core/session')->setData('ipcheck_redirected', Mage::app()->getStore()->getId());
        }

        $url = $this->getRequest()->getParam('url');
        if (strpos($url, 'http') === 0) {
            $this->_redirectUrl($url);
        } else {
            $this->_redirect($url);
        }
    }

    public function ordersAction()
    {
        $server = $this->getServer();
        $server->setRestDispatchModel(Mage::getModel('japi/rest_mage'));
        $server->run();
    }

    public function eventsAction()
    {
        $server = $this->getServer();
        $server->setRestDispatchModel(Mage::getModel('japi/rest_mage'));
        $server->run();
    }
}