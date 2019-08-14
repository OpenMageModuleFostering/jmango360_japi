<?php

class Jmango360_Japi_Model_Observer
{
    public function TIG_PostNL__addressBookPostcodeCheck($observe)
    {
        if (Mage::app()->getRequest()->getModuleName() != 'japi') return;
        if (!Mage::helper('core')->isModuleEnabled('TIG_PostNL')) return;
        if (!class_exists('TIG_PostNL_Model_AddressValidation_Observer_AddressBook')) return;
        if (!class_exists('TIG_PostNL_Helper_AddressValidation')) return;
        $blockClass = Mage::getConfig()->getBlockClassName(TIG_PostNL_Model_AddressValidation_Observer_AddressBook::ADDRESS_COMMUNITY_BLOCK_NAME);
        $block = $observe->getBlock();
        if (!$block || get_class($block) != $blockClass) return;
        if (!Mage::helper('postnl/addressValidation')->isPostcodeCheckEnabled(null, TIG_PostNL_Model_AddressValidation_Observer_AddressBook::POSTCODECHECK_ENV)) return;
        Mage::app()->getStore()->setConfig(TIG_PostNL_Helper_AddressValidation::XPATH_POSTCODE_CHECK_IN_ADDRESSBOOK, 0);
        $block->setTemplate('japi/TIG/PostNL/av/customer/address/edit.phtml');
    }

    public function TIG_PostNL__shippingAddressPostcodeCheck($observe)
    {
        if (Mage::app()->getRequest()->getModuleName() != 'japi') return;
        if (!Mage::helper('core')->isModuleEnabled('TIG_PostNL')) return;
        if (!class_exists('TIG_PostNL_Model_AddressValidation_Observer_Onepage')) return;
        if (!class_exists('TIG_PostNL_Helper_AddressValidation')) return;
        $blockClass = Mage::getConfig()->getBlockClassName(TIG_PostNL_Model_AddressValidation_Observer_Onepage::SHIPPING_ADDRESS_BLOCK_NAME);
        $block = $observe->getBlock();
        if (!$block || get_class($block) != $blockClass) return;
        if (Mage::getSingleton('core/session')->hasData(TIG_PostNL_Helper_AddressValidation::XPATH_POSTCODE_CHECK_IN_CHECKOUT)) {
            Mage::app()->getStore()->setConfig(TIG_PostNL_Helper_AddressValidation::XPATH_POSTCODE_CHECK_IN_CHECKOUT, 1);
        }
        if (!Mage::helper('postnl/addressValidation')->isPostcodeCheckEnabled(null, TIG_PostNL_Model_AddressValidation_Observer_Onepage::POSTCODECHECK_ENV)) return;
        Mage::app()->getStore()->setConfig(TIG_PostNL_Helper_AddressValidation::XPATH_POSTCODE_CHECK_IN_CHECKOUT, 0);
        $block->setTemplate('japi/TIG/PostNL/av/checkout/onepage/shipping.phtml');
    }

    public function TIG_PostNL__billingAddressPostcodeCheck($observe)
    {
        if (Mage::app()->getRequest()->getModuleName() != 'japi') return;
        if (!Mage::helper('core')->isModuleEnabled('TIG_PostNL')) return;
        if (!class_exists('TIG_PostNL_Model_AddressValidation_Observer_Onepage')) return;
        if (!class_exists('TIG_PostNL_Helper_AddressValidation')) return;
        $blockClass = Mage::getConfig()->getBlockClassName(TIG_PostNL_Model_AddressValidation_Observer_Onepage::BILLING_ADDRESS_BLOCK_NAME);
        $block = $observe->getBlock();
        if (!$block || get_class($block) != $blockClass) return;
        if (!Mage::helper('postnl/addressValidation')->isPostcodeCheckEnabled(null, TIG_PostNL_Model_AddressValidation_Observer_Onepage::POSTCODECHECK_ENV)) return;
        Mage::app()->getStore()->setConfig(TIG_PostNL_Helper_AddressValidation::XPATH_POSTCODE_CHECK_IN_CHECKOUT, 0);
        Mage::getSingleton('core/session')->setData(TIG_PostNL_Helper_AddressValidation::XPATH_POSTCODE_CHECK_IN_CHECKOUT, 1);
        $block->setTemplate('japi/TIG/PostNL/av/checkout/onepage/billing.phtml');
    }

    public function TIG_PostNL__addDeliveryOptions($observe)
    {
        if (Mage::app()->getRequest()->getModuleName() != 'japi') return;
        if (!Mage::helper('core')->isModuleEnabled('TIG_PostNL')) return;
        if (!class_exists('TIG_PostNL_Model_DeliveryOptions_Observer_ShippingMethodAvailable')) return;
        if (!class_exists('TIG_PostNL_Helper_DeliveryOptions')) return;
        $blockClass = Mage::getConfig()->getBlockClassName(TIG_PostNL_Model_DeliveryOptions_Observer_ShippingMethodAvailable::BLOCK_NAME);
        $block = $observe->getBlock();
        if (!$block || get_class($block) != $blockClass) return;
        /* @var $model TIG_PostNL_Model_DeliveryOptions_Observer_ShippingMethodAvailable */
        $model = Mage::getSingleton('postnl_deliveryoptions/observer_shippingMethodAvailable');
        if (!$model->getCanUseDeliveryOptions()) return;
        $model->setBpostBlockModified(true);
        //Mage::app()->getStore()->setConfig(TIG_PostNL_Helper_DeliveryOptions::XPATH_DELIVERY_OPTIONS_ACTIVE, 0);
        $block->setTemplate('japi/TIG/PostNL/do/onepage/available.phtml');
    }

    public function handleErrorCheckout($observe)
    {
        /* @var $server Jmango360_Japi_Model_Server */
        $server = Mage::getSingleton('japi/server');
        if ($server->getIsRest() && $server->getIsSubmit()) {
            $server->unsetIsSubmit();

            /* @var $action Mage_Core_Controller_Varien_Action */
            $action = $observe->getControllerAction();
            if (!$action) return;

            /* @var $coreSession Mage_Core_Model_Session */
            $coreSession = Mage::getSingleton('core/session');
            /* @var $checkoutSession Mage_Checkout_Model_Session */
            $checkoutSession = Mage::getSingleton('checkout/session');
            $messages = $coreSession->getMessages(true);
            foreach ($messages->getItems() as $message) {
                $checkoutSession->addMessage($message);
            }

            $request = $action->getRequest();
            $request->initForward()
                ->setModuleName('japi')
                ->setControllerName('checkout')
                ->setActionName('onepage')
                ->setDispatched(false);
        }
    }

    public function skipPaypalExpressReview()
    {
        /* @var $server Jmango360_Japi_Model_Server */
        $server = Mage::getSingleton('japi/server');
        if ($server->getIsRest()) {
            try {
                if (defined('Mage_Paypal_Model_Config::XML_PATH_PAYPAL_EXPRESS_SKIP_ORDER_REVIEW_STEP_FLAG')) {
                    Mage::app()->getStore()->setConfig(Mage_Paypal_Model_Config::XML_PATH_PAYPAL_EXPRESS_SKIP_ORDER_REVIEW_STEP_FLAG, 1);
                }
                Mage::app()->getStore()->setConfig('checkout/options/enable_agreements', 0);
            } catch (Exception $e) {
                Mage::logException($e);
            }
        }
    }

    protected function _getOrderIncrementIdById($orderId)
    {
        if (!$orderId) return null;
        return Mage::getResourceModel('sales/order')->getIncrementId($orderId);
    }

    public function setOrderIdToHeader()
    {
        /* @var $server Jmango360_Japi_Model_Server */
        $server = Mage::getSingleton('japi/server');
        if ($server->getIsRest()) {
            /* @var $session Mage_Checkout_Model_Session */
            $session = Mage::getSingleton('checkout/session');
            $lastRealOrderId = $session->getLastRealOrderId();
            if (!$lastRealOrderId) {
                $lastRealOrderId = $this->_getOrderIncrementIdById($session->getLastOrderId());
            }
            if ($lastRealOrderId) {
                Mage::app()->getFrontController()->getResponse()->setHeader('Last-Real-Order-Id', $lastRealOrderId, true);
            }
            $lastQuoteId = $session->getLastQuoteId();
            if ($lastQuoteId) {
                /* @var $quote Mage_Sales_Model_Quote */
                $quote = Mage::getModel('sales/quote')->load($lastQuoteId);
                if ($quote && $quote->getId() && $quote->getIsActive() == 1) {
                    $quote->setIsActive(0)->save();
                }
            }
        }
    }

    public function japiLayoutGenerateBlocksAfter($observe)
    {
        /* @var $server Jmango360_Japi_Model_Server */
        $server = Mage::getSingleton('japi/server');
        if ($server->getIsRest()) {
            $request = Mage::app()->getRequest();
            /* @var $layout Mage_Core_Model_Layout */
            $layout = $observe->getLayout();
            if (!$layout) return;
            $head = $layout->getBlock('head');
            if (!$head) return;

            /* @var $session Mage_Checkout_Model_Session */
            $session = Mage::getSingleton('checkout/session');
            $lastRealOrderId = $session->getLastRealOrderId();
            if (!$lastRealOrderId) {
                $lastRealOrderId = $this->_getOrderIncrementIdById($session->getLastOrderId());
            }

            /**
             * Inject script to automatic launch mobile app
             */
            if ($lastRealOrderId) {
                $block = $layout->createBlock('japi/js');
                $block->setOrderId($lastRealOrderId);
                $head->append($block);
            }

            /**
             * Append newly created order ID to response header
             */
            if ($lastRealOrderId) {
                $block = $layout->createBlock('core/text');
                $block->setText(sprintf('<meta name="%s" content="%s">', 'last-real-order-id', $lastRealOrderId));
                $head->append($block, 'last-real-order-id');
            }

            /**
             * Append customer data to submited customer edit page
             */
            if ($request->getModuleName() == 'japi' && $request->getControllerName() == 'customer' && $request->getActionName() == 'edit') {
                /* @var $customerSession Mage_Customer_Model_Session */
                $customerSession = Mage::getSingleton('customer/session');
                if ($customerSession->getIsSubmit() && !$customerSession->getMessages()->getErrors()) {
                    $block = $layout->createBlock('core/text');
                    $tags[] = sprintf('<meta name="%s" content="%s">', 'JM-Account-Id', $customerSession->getCustomerId());
                    $tags[] = sprintf('<meta name="%s" content="%s">', 'JM-Account-Email', $customerSession->getCustomer()->getEmail());
                    $block->setText(join("\n", $tags));
                    $head->append($block, uniqid());
                }
            }

            /**
             * Append customer data to submited customer register page
             */
            if ($request->getModuleName() == 'japi' && $request->getControllerName() == 'customer' && $request->getActionName() == 'register') {
                /* @var $customerSession Mage_Customer_Model_Session */
                $customerSession = Mage::getSingleton('customer/session');
                if ($customerSession->getIsSubmit() && !$customerSession->getMessages()->getErrors()) {
                    $block = $layout->createBlock('core/text');
                    $tags[] = sprintf('<meta name="%s" content="%s">', 'JM-Account-Id', $customerSession->getCustomerId());
                    $tags[] = sprintf('<meta name="%s" content="%s">', 'JM-Account-Email', $customerSession->getCustomerEmail());
                    $tags[] = sprintf('<meta name="%s" content="%s">', 'JM-Confirmation-Required', $customerSession->getIsConfirmationRequired());
                    $tags[] = sprintf('<meta name="%s" content="%s">', 'JM-Session-Id', $customerSession->getSessionId());
                    $block->setText(join("\n", $tags));
                    $head->append($block, uniqid());
                }
            }

            /**
             * Append customer data to submited customer new address page
             */
            if ($request->getModuleName() == 'japi' && $request->getControllerName() == 'customer' && $request->getActionName() == 'address') {
                /* @var $customerSession Mage_Customer_Model_Session */
                $customerSession = Mage::getSingleton('customer/session');
                if ($customerSession->getIsSubmit() && !$customerSession->getMessages()->getErrors()) {
                    $block = $layout->createBlock('core/text');
                    $tags[] = sprintf('<meta name="%s" content="%s">', 'JM-Account-Id', $customerSession->getCustomerId());
                    $tags[] = sprintf('<meta name="%s" content="%s">', 'JM-Account-Email', $customerSession->getCustomer()->getEmail());
                    $tags[] = sprintf('<meta name="%s" content="%s">', 'JM-Address-Id', $request->getParam('id'));
                    $block->setText(join("\n", $tags));
                    $head->append($block, uniqid());
                }
            }
        }
    }

    public function customerRegisterSuccess($observe)
    {
        if (!Mage::getSingleton('core/session')->getIsRest()) return;
        /* @var $customer Mage_Customer_Model_Customer */
        $customer = $observe->getEvent()->getCustomer();
        if (!$customer->getId()) return;
        /* @var $session Mage_Customer_Model_Session */
        $session = Mage::getSingleton('customer/session');
        $session->setIsSubmit(true);
        $session->setCustomerId($customer->getId());
        $session->setCustomerEmail($customer->getEmail());
        $session->setIsConfirmationRequired($customer->isConfirmationRequired());
    }

    public function restAdminActionPreDispatch($observe)
    {
        /* @var $action Mage_Core_Controller_Varien_Action */
        $action = $observe->getEvent()->getControllerAction();
        switch ($action->getFullActionName()) {
            case 'adminhtml_report_statistics_refreshRecent':
                $codes = $this->_getRefreshStatisticCodes($action->getRequest());
                if (in_array('sales', $codes)) {
                    $currentDate = Mage::app()->getLocale()->date();
                    $date = $currentDate->subHour(25);
                    Mage::getResourceModel('japi/sales_report_order')->aggregate($date);
                }
                break;
            case 'adminhtml_report_statistics_refreshLifetime':
                $codes = $this->_getRefreshStatisticCodes($action->getRequest());
                if (in_array('sales', $codes)) {
                    Mage::getResourceModel('japi/sales_report_order')->aggregate();
                }
                break;
        }

        // Remove plugin update notification
        //$this->checkPluginUpdate();
    }

    public function aggregateSalesReportOrderData()
    {
        try {
            Mage::app()->getLocale()->emulate(0);
            $currentDate = Mage::app()->getLocale()->date();
            $now = clone $currentDate;
            $date = $currentDate->subHour(25);
            $this->_logCronjob(sprintf('Start from %s to %s', $date, $now));
            Mage::getResourceModel('japi/sales_report_order')->aggregate($date);
            Mage::app()->getLocale()->revert();
            $this->_logCronjob("End\n");
            return 1;
        } catch (Exception $e) {
            Mage::logException($e);
            return 0;
        }
    }

    public function reindexProductAttributeData()
    {
        try {
            if (Mage::getStoreConfigFlag('japi/indexer/product_attribute_show_in_jm360')) return 2;
            /* @var $productCollection Mage_Catalog_Model_Resource_Product_Collection */
            $productCollection = Mage::getResourceSingleton('catalog/product_collection');
            $productCollection->addFieldToFilter('type_id', array('in' => array('simple', 'configurable', 'grouped', 'bundle')));
            $productIds = $productCollection->getAllIds();
            /* @var $productAction Mage_Catalog_Model_Product_Action */
            $productAction = Mage::getSingleton('catalog/product_action');
            $productAction->updateAttributes($productIds, array('show_in_jm360' => '1'), 0);
            Mage::getConfig()->saveConfig('japi/indexer/product_attribute_show_in_jm360', 1);
            Mage::app()->cleanCache(array(Mage_Core_Model_Config::CACHE_TAG));
            return 1;
        } catch (Exception $e) {
            Mage::logException($e);
            return 0;
        }
    }

    protected function _logCronjob($str)
    {
        Mage::log($str, null, 'japi_cron.log');
    }

    /**
     * @param $request Mage_Core_Controller_Request_Http
     * @return array
     */
    protected function _getRefreshStatisticCodes($request)
    {
        $codes = $request->getParam('code');

        if (!is_array($codes) && strpos($codes, ',') === false) {
            $codes = array($codes);
        } elseif (!is_array($codes)) {
            $codes = explode(',', $codes);
        }

        return $codes;
    }

    protected function checkPluginUpdate()
    {
        if (Mage::getSingleton('admin/session')->isLoggedIn()) {
            /* @var $_helper Jmango360_Japi_Helper_Data */
            $_helper = Mage::helper('japi');

            /* @var $feedModel Mage_AdminNotification_Model_Feed */
            $feedModel = Mage::getModel('adminnotification/feed');
            if (($feedModel->getFrequency() + $_helper->getLastCheckUpdate()) > time()) {
                return $this;
            }

            /* @var $session Mage_Adminhtml_Model_Session */
            $session = Mage::getSingleton('adminhtml/session');

            if ($session->getJapiCheckedUpdate() == 'checked') {
                return $this;
            }

            $inboxModel = Mage::getModel('adminnotification/inbox');
            $inboxResource = $inboxModel->getResource();

            $_versionInfo = $_helper->getUpdateAvailable();
            if (!$_versionInfo) {
                return $this;
            }

            $_messageData = array(
                'severity' => 4,
                'date_added' => gmdate('Y-m-d H:i:s', time()),
                'title' => Mage::helper('japi')->__('A new version of the JMango360 Mobile plugin %s is available. Please update.', $_versionInfo['connectVer']),
                'description' => Mage::helper('japi')->__('A new version of the JMango360 Mobile plugin %s is available. Please update.', $_versionInfo['connectVer']),
                'url' => 'https://www.magentocommerce.com/magento-connect/jmango360-rest-plugin.html'
            );

            /* @var $_coreResource Mage_Core_Model_Resource */
            $_coreResource = Mage::getSingleton('core/resource');

            if (!$_versionInfo['needUpdate']) { //If not need update
                // Delete all our messages if have not need update version available
                $write = $_coreResource->getConnection('core_write');
                $write->delete(
                    $inboxResource->getMainTable(),
                    'url = "' . $_messageData['url'] . '"'
                );

                $session->setJapiNotificationData(null);
                $session->setJapiCheckedUpdate('checked');
                $_helper->setLastCheckUpdate();
                return $this;
            }

            /**
             * Add new notification if not exist when new version update available
             */
            $adapter = $_coreResource->getConnection('core_read');
            $select = $adapter->select()
                ->from($inboxResource->getMainTable())
                ->order($inboxResource->getIdFieldName() . ' DESC')
                ->where('description = "' . $_messageData['description'] . '"')
                ->where('is_remove != 1')
                ->limit(1);
            $data = $adapter->fetchRow($select);
            if (!$data) {
                $inboxModel->addNotice($_messageData['title'], $_messageData['description'], $_messageData['url']);
            }

            $session->setJapiNotificationData(null);
            $session->setJapiCheckedUpdate('checked');
            $_helper->setLastCheckUpdate();
        }
    }

    /**
     * Append new column to orders, customers grid
     */
    public function coreBlockAbstractToHtmlBefore($observe)
    {
        /* @var $grid Mage_Adminhtml_Block_Widget_Grid */
        $grid = $observe->getEvent()->getBlock();
        /* @var $helper Jmango360_Japi_Helper_Data */
        $helper = Mage::helper('japi');

        switch ($grid->getType()) {
            case 'adminhtml/sales_order_grid':
                if (!Mage::getStoreConfigFlag('japi/jmango_rest_sales_settings/display_order_from')) {
                    return;
                }

                if (!$helper->hasJapiOrderData()) {
                    return;
                }

                $grid->addColumnAfter('japi', array(
                    'header' => $helper->__('Order From'),
                    'index' => 'japi',
                    'filter_index' => 'main_table.japi',
                    'type' => 'options',
                    'width' => '70px',
                    'options' => array(
                        1 => $helper->__('JMango360')
                    )
                ), 'real_order_id');
                break;
            case 'adminhtml/customer_grid':
                if (!Mage::getStoreConfigFlag('japi/jmango_rest_customer_settings/display_customer_from')) {
                    return;
                }

                if (!$helper->hasJapiCustomerData()) {
                    return;
                }

                $grid->addColumnAfter('japi', array(
                    'header' => $helper->__('JMango360 User'),
                    'index' => 'japi',
                    'type' => 'options',
                    'width' => '70px',
                    'options' => array(
                        0 => $helper->__('No'),
                        1 => $helper->__('Yes')
                    ),
                    'filter_condition_callback' => array($this, 'japiCustomerFilterConditionCallback')
                ), 'entity_id');
                break;
            case 'adminhtml/catalog_product_grid':
                if (!Mage::getStoreConfigFlag('japi/jmango_rest_catalog_settings/visible_on_app')) {
                    return;
                }

                if (!$helper->hasJapiProductData()) {
                    return;
                }

                $grid->addColumnAfter('hide_in_jm360', array(
                    'header' => $helper->__('Hide on JMango360'),
                    'index' => 'hide_in_jm360',
                    'type' => 'options',
                    'width' => '70px',
                    'options' => array(
                        0 => $helper->__('No'),
                        1 => $helper->__('Yes')
                    ),
                    'renderer' => 'Jmango360_Japi_Block_Adminhtml_Catalog_Product_Grid_Column_Renderer_Hide'
                ), 'visibility');
                break;
        }
    }

    /**
     * Inject product collection to join 'japi' filter
     * Inject customer collection to join 'japi' filter
     */
    public function eavCollectionAbstractLoadBefore($observe)
    {
        /* @var $helper Jmango360_Japi_Helper_Data */
        $helper = Mage::helper('japi');

        /* @var $collection Varien_Data_Collection_Db */
        $collection = $observe->getEvent()->getCollection();
        if (!$collection) return;

        if ($collection instanceof Mage_Customer_Model_Resource_Customer_Collection) {
            if (Mage::getStoreConfigFlag('japi/jmango_rest_customer_settings/display_customer_from') && $helper->hasJapiCustomerData()) {
                $this->_addJapiToCustomerSelect($collection);
            }
        }

        if ($collection instanceof Mage_Catalog_Model_Resource_Product_Collection) {
            if (Mage::getStoreConfigFlag('japi/jmango_rest_catalog_settings/visible_on_app') && $helper->hasJapiProductData()) {
                //$this->_addJapiToProductSelect($collection);
                $collection->addAttributeToSelect('hide_in_jm360');
            }
        }
    }

    /**
     * Inject order grid collection to add 'japi' filter
     */
    public function coreCollectionAbstractLoadBefore($observe)
    {
        /* @var $collection Mage_Core_Model_Resource_Db_Collection_Abstract */
        $collection = $observe->getEvent()->getCollection();
        if (!$collection) return;

        switch (get_class($collection)) {
            /**
             * MPLUGIN-1510: Support IWD_OrderManager v1.7.2.1
             * MPLUGIN-1843: Support IWD_OrderManager v1.6.0.4
             */
            case 'IWD_OrderManager_Model_Resource_Order_Grid_Collection':
                try {
                    $columns = $collection->getSelect()->getPart('columns');
                    $needAddColumn = true;
                    if (is_array($columns)) {
                        foreach ($columns as $column) {
                            if (in_array('*', $column) || in_array('japi', $column)) {
                                $needAddColumn = false;
                            }
                        }
                    }
                    if ($needAddColumn) {
                        $collection->addFieldToSelect('japi');
                    }
                } catch (Exception $e) {
                }
                break;
        }
    }

    /**
     * Customer mobile filter callback
     *
     * @param $collection Mage_Customer_Model_Resource_Customer_Collection
     * @param $column Mage_Adminhtml_Block_Widget_Grid_Column
     */
    public function japiCustomerFilterConditionCallback($collection, $column)
    {
        $this->_addJapiToCustomerSelect($collection);
        $collection->addAttributeToFilter('japi', array('eq' => $column->getFilter()->getValue()));
    }

    /**
     * Add japi with ifnull condition to customer select
     *
     * @param Mage_Customer_Model_Resource_Customer_Collection $collection
     */
    protected function _addJapiToCustomerSelect($collection)
    {
        try {
            $fromPart = $collection->getSelect()->getPart('from');
            if (array_key_exists('at_japi', $fromPart)) return;

            $adapter = $collection->getConnection();
            $collection->addExpressionAttributeToSelect('japi', $adapter->getIfNullSql('{{japi}}', 0), array('japi'));
        } catch (Exception $e) {
            Mage::logException($e);
        }
    }

    /**
     * Support Vaimo_Klarna
     *
     * @param Varien_Event_Observer $observer
     * @return $this
     */
    public function Vaimo_Klarna__checkLaunchKlarnaCheckout(Varien_Event_Observer $observer)
    {
        if (!Mage::helper('core')->isModuleEnabled('Vaimo_Klarna'))
            return $this;

        /* @var $obj Vaimo_Klarna_Model_Observer */
        $obj = Mage::getSingleton('klarna/observer');

        if (method_exists($obj, 'checkLaunchKlarnaCheckout')) {
            $obj->checkLaunchKlarnaCheckout($observer);
        }
    }

    /**
     * Support Vaimo_Klarna
     * Redirect to JMango360 checkout success page
     *
     * @param Varien_Event_Observer $observer
     * @return $this
     */
    public function Vaimo_Klarna__checkoutKlarnaSuccess(Varien_Event_Observer $observer)
    {
        /* @var $server Jmango360_Japi_Model_Server */
        $server = Mage::getSingleton('japi/server');
        if ($server->getIsRest()) {
            Mage::app()->getFrontController()->getResponse()->setRedirect(Mage::getUrl('japi/klarna/success'));
        }
    }

    /**
     * MPLUGIN-1324: fix issue "Session expired" for Japi checkout onepage
     *
     * @param Varien_Event_Observer $observer
     * @return $this
     */
    public function japiOnepagePreDispatch(Varien_Event_Observer $observer)
    {
        if (strpos(Mage::getBaseUrl(), 'eleganza') === false) return $this;

        $session = Mage::getSingleton('core/session');
        $sessionId = $session->getSessionId();
        /** @var Mage_Core_Model_Cookie $cookie */
        $cookie = Mage::getModel('core/cookie');
        $cookie->set('frontend', $sessionId, null, '/japi/checkout', Mage::app()->getRequest()->getHttpHost(), null, true);
    }

    /**
     * MPLUGIN-1446: Support Klevu_Search
     *
     * @param Varien_Event_Observer $observer
     * @return $this
     */
    public function Klevu_Search__applyLandingPageModelRewrites(Varien_Event_Observer $observer)
    {
        if (!Mage::helper('core')->isModuleEnabled('Klevu_Search'))
            return $this;

        /* @var $obj Klevu_Search_Model_Observer */
        $obj = Mage::getSingleton('klevu_search/observer');

        if (method_exists($obj, 'applyLandingPageModelRewrites')) {
            $obj->applyLandingPageModelRewrites($observer);
        }
    }

    /**
     * MPLUGIN-1412: Support Kega_Checkout
     *
     * @param Varien_Event_Observer $observer
     * @return $this
     */
    public function controllerActionPredispatchCheckoutOnepageSaveShippingMethod(Varien_Event_Observer $observer)
    {
        if (Mage::helper('core')->isModuleEnabled('Kega_Checkout')) {
            /* @var $obj Kega_Checkout_Model_Onepage_Observer */
            $obj = Mage::getSingleton('kega_checkout/onepage_observer');

            if (method_exists($obj, 'correctAddressData')) {
                $obj->correctAddressData($observer);
            }

            if (method_exists($obj, 'saveShippingAddress')) {
                $obj->saveShippingAddress($observer);
            }
        }

        if (Mage::helper('core')->isModuleEnabled('Kega_StorePickup')) {
            /* @var $obj Kega_StorePickup_Model_Observer */
            $obj = Mage::getSingleton('storepickup/observer');

            if (method_exists($obj, 'saveStorePickupToQuote')) {
                $obj->saveStorePickupToQuote($observer);
            }
        }
    }

    /**
     * Support TIG_PostNL
     *
     * @param Varien_Event_Observer $observer
     * @return $this
     */
    public function controllerActionPostdispatchCheckoutOnepageSaveShippingMethod(Varien_Event_Observer $observer)
    {
        if (Mage::helper('core')->isModuleEnabled('TIG_PostNL')) {
            /* @var $obj TIG_PostNL_Model_DeliveryOptions_Observer_UpdatePostnlOrder */
            $obj = Mage::getSingleton('postnl_deliveryoptions/observer_updatePostnlOrder');

            if (method_exists($obj, 'saveOptions')) {
                $obj->saveOptions($observer);
            }
        }

        if (Mage::helper('core')->isModuleEnabled('Kega_Checkout')) {
            /* @var $obj Kega_Checkout_Model_Onepage_Observer */
            $obj = Mage::getSingleton('kega_checkout/onepage_observer');

            if (method_exists($obj, 'applyShippingErrors')) {
                $obj->applyShippingErrors($observer);
            }
        }
    }

    /**
     * Forward some custom actions to our actions
     * Support: NWT_KCO
     *
     * @param Varien_Event_Observer $observer
     * @return $this
     */
    public function japiControllerActionPredispatch(Varien_Event_Observer $observer)
    {
        /* @var $action Mage_Core_Controller_Varien_Action */
        $action = $observer->getEvent()->getControllerAction();
        if ($action->getRequest()->getModuleName() == 'kco') {
            if (strpos($action->getRequest()->getServer('HTTP_REFERER'), 'japi/kco') !== false) {
                $action->getRequest()
                    ->initForward()
                    ->setModuleName('japi')
                    ->setControllerName('kco')
                    ->setActionName($action->getRequest()->getActionName())
                    ->setDispatched(false);
            }
        }

        return $this;
    }

    /**
     * @param Varien_Event_Observer $observer
     */
    public function japiControllerResponseRedirect(Varien_Event_Observer $observer)
    {
        $transport = $observer->getEvent()->getTransport();
        /* @var $server Jmango360_Japi_Model_Server */
        $server = Mage::getSingleton('japi/server');
        if ($server->getIsRest()) {
            /**
             * MPLUGIN-1969: Try spoof LaPoste_SoColissimoSimplicite not redirect to "checkout/onepage"
             */
            if (strpos($transport->getUrl(), 'checkout/onepage') !== false) {
                /* @var $checkoutSession Mage_Checkout_Model_Session */
                $checkoutSession = Mage::getSingleton('checkout/session');
                if ($checkoutSession->getData('socolissimosimplicite_checkout_onepage_nextstep') == 'payment') {
                    $transport->setUrl(Mage::getUrl('japi/checkout/onepage', array('_secure' => true)));
                }
            }
        }
    }

    /**
     * Mobile Paypal SDK: Save verify comment to order
     *
     * @param Varien_Event_Observer $observer
     */
    public function japiSalesModelServiceQuoteSubmitBefore(Varien_Event_Observer $observer)
    {
        /* @var $session Mage_Checkout_Model_Session */
        $session = Mage::getSingleton('checkout/session');
        if ($session->getData('jmango_payment_paypal_verified')) {
            /* @var $order Mage_Sales_Model_Order */
            $order = $observer->getEvent()->getOrder();
            $paymentId = $order->getPayment()->getAdditionalInformation(Jmango360_Japi_Model_Payment::PAYPAL_PAYMENT_ID);
            $order->addStatusHistoryComment(sprintf('Paypal payment ID (%s) verified.', $paymentId));
        }
    }
}
