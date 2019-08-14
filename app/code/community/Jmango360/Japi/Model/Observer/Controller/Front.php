<?php
/**
 * Copyright 2017 JMango360
 */

/**
 * Class Jmango360_Japi_Model_Observer_Controller_Front
 * The purpose for this class is help reduce impact to live site when debuging,
 * because we listen on critical event "controller_front_init_before", which always fire in any request.
 * Crazy huh!
 */
class Jmango360_Japi_Model_Observer_Controller_Front
{
    /**
     * Try inject our custom core/session model
     * This lead to use SID in any conditional
     *
     * @param Varien_Event_Observer $observe
     */
    public function controllerFrontInitBefore($observe)
    {
        /* @var $helper Jmango360_Japi_Helper_Data */
        $helper = Mage::helper('japi');

        /**
         * Set current store if exist
         */
        $storeId = Mage::app()->getRequest()->getParam('store_id', null);
        if ($storeId) {
            Mage::app()->setCurrentStore($storeId);
        }

        if ($helper->isNeedByPassSessionValidation() || $helper->isNeedByPassMIMT() || !$helper->isUseSidFrontend()) {
            /* @var $front Mage_Core_Controller_Varien_Front */
            $front = $observe->getEvent()->getFront();
            $request = $front->getRequest();
            $route = explode('/', $request->getPathInfo());
            if (in_array('system_config', $route)) return;
            if (count($route) > 3 && $route[1] == 'japi') {
                if (!$this->_getListModuleNeedToByPassSession() && ($route[2] == 'checkout' && $route[3] == 'onepage')) {
                    return;
                }
                if($store = Mage::app()->getRequest()->getParam('___store', false)) {
                    Mage::app()->setCurrentStore(Mage::app()->getStore($store)->getId());
                }
                Mage::register('_singleton/core/session', Mage::getModel('japi/core_session', array('name' => 'frontend')), true);
            } elseif (count($route) > 3 && in_array('japi', $route)) {
                if (!$this->_getListModuleNeedToByPassSession() && (in_array('checkout', $route) && in_array('onepage', $route))) {
                    return;
                }
                if($store = Mage::app()->getRequest()->getParam('___store', false)) {
                    Mage::app()->setCurrentStore(Mage::app()->getStore($store)->getId());
                }
                Mage::register('_singleton/core/session', Mage::getModel('japi/core_session', array('name' => 'frontend')), true);
            } elseif (strpos(Mage::app()->getRequest()->getHeader('Referer'), 'japi/checkout/onepage') !== false) {
                if (!$this->_getListModuleNeedToByPassSession()) {
                    return;
                }
                if($store = Mage::app()->getRequest()->getParam('___store', false)) {
                    Mage::app()->setCurrentStore(Mage::app()->getStore($store)->getId());
                }
                Mage::register('_singleton/core/session', Mage::getModel('japi/core_session', array('name' => 'frontend')), true);
            }
        }
    }

    /**
     * What modules should bypass core session validation
     * MPLUGIN-1893: Refactor for flexible solution
     *
     * @return bool
     */
    protected function _getListModuleNeedToByPassSession()
    {
        $modules = explode("\n", Mage::getStoreConfig('japi/jmango_rest_developer_settings/exclude_modules'));
        if (!count($modules)) return false;
        $helper = Mage::helper('core');
        foreach ($modules as $module) {
            if ($helper->isModuleEnabled(trim($module))) {
                return true;
            }
        }
    }
}