<?php
if (Mage::helper('core')->isModuleEnabled('Flagbit_LanguageSelector') && @class_exists('Flagbit_LanguageSelector_Model_Observer')) {
    class Jmango360_Japi_Model_Flagbit_LanguageSelector_Observer extends Flagbit_LanguageSelector_Model_Observer
    {
        public function redirectByBrowserLang($observe)
        {
            if ($this->_japiBypass($observe)) {
                return;
            }

            parent::redirectByBrowserLang($observe);
        }

        public function syncCookieWithSession($observe)
        {
            if ($this->_japiBypass($observe)) {
                return;
            }

            parent::syncCookieWithSession($observe);
        }

        protected function _getStoreToRedirect()
        {
            if (Mage::app()->getRequest()->getModuleName() == 'japi') {
                return Mage::app()->getStore();
            }
            return parent::_getStoreToRedirect();
        }

        /**
         * @param Varien_Event_Observer $observe
         * @return bool
         */
        protected function _japiBypass($observe)
        {
            /** @var Mage_Core_Controller_Varien_Action $action */
            $action = $observe->getEvent()->getControllerAction();
            $moduleName = $action->getRequest()->getModuleName();
            if ($moduleName == 'japi') {
                if ($action->getRequest()->getActionName() == 'onepage') {
                    return false;
                }
                return true;
            } elseif ($moduleName == 'adyen') {
                return true;
            }
        }
    }
} else {
    class Jmango360_Japi_Model_Flagbit_LanguageSelector_Observer
    {
    }
}