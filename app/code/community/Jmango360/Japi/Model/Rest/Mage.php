<?php

class Jmango360_Japi_Model_Rest_Mage extends Mage_Core_Model_Abstract
{
    const APIUSERPATH = 'japi/jmango_rest_api/apiuser';
    const APIKEYPATH = 'japi/jmango_rest_api/apikey';
    const USEFRONTENDSIDPATH = 'web/session/use_frontend_sid';

    const PATH_CURRENCY_BASE = 'currency/options/base';
    const PATH_CURRENCY_DEFAULT = 'currency/options/default';
    const PATH_CURRENCY_ALLOW = 'currency/options/allow';
    const PATH_DEFAULT_COUNTRY = 'general/country/default';
    const PATH_IDEV_DEFAULT_COUNTRY = 'onestepcheckout/general/default_country';
    const PATH_COUNTRY_ALLOW = 'general/country/allow';
    const PATH_OPTIONAL_POSTCODE = 'general/country/optional_zip_countries';
    const PATH_STATE_REQUIRED = 'general/region/state_required';
    const PATH_DISPLAY_ALL = 'general/region/display_all';
    const PATH_TIMEZONE = 'general/locale/timezone';
    const PATH_LOCALE = 'general/locale/code';
    const PATH_GUEST_CHECKOUT = 'checkout/options/guest_checkout';
    const PATH_WSI_COMPLIANCE = 'api/config/compliance_wsi';
    //const PATH_SHOW_STOCK_INFO = 'cataloginventory/options/display_product_stock_status';
    const PATH_SHOW_STOCK_INFO = 'japi/jmango_rest_stock_settings/display_product_stock_status';
    const PATH_THEME_CONFIG = 'japi/jmango_rest_theme/data';

    protected $_attributes = array();
    protected $_excludeCustomerAttributes = array(
        'prefix', 'firstname', 'middlename', 'suffix', 'lastname', 'email', 'dob', 'taxvat', 'gender'
    );
    protected $_excludeAddressAttributes = array(
        'prefix', 'firstname', 'middlename', 'suffix', 'lastname', 'company', 'street', 'city', 'country_id',
        'region', 'region_id', 'postcode', 'telephone', 'fax', 'vat_id'
    );
    protected $_excludeSettingGroups = array(
        'jmango_rest_api', 'jmango_rest_developer_settings'
    );

    public function dispatch()
    {
        $action = $this->_getRequest()->getAction();
        $operation = $this->_getRequest()->getOperation();

        switch ($action . $operation) {
            case 'store' . Jmango360_Japi_Model_Request::OPERATION_RETRIEVE:
                $data = $this->_store();
                $this->_getResponse()->render($data);
                $this->_getResponse()->setHttpResponseCode(Jmango360_Japi_Model_Server::HTTP_OK);
                break;
            case 'store' . Jmango360_Japi_Model_Request::OPERATION_UPDATE:
                $data = $this->_store(true);
                $this->_getResponse()->render($data);
                $this->_getResponse()->setHttpResponseCode(Jmango360_Japi_Model_Server::HTTP_OK);
                break;
            case 'getSession' . Jmango360_Japi_Model_Request::OPERATION_CREATE:
                $data = $this->_getNewSessionId();
                $this->_getResponse()->render($data);
                $this->_getResponse()->setHttpResponseCode(Jmango360_Japi_Model_Server::HTTP_CREATED);
                break;
            case 'getMagentoInfo' . Jmango360_Japi_Model_Request::OPERATION_RETRIEVE:
                $data = $this->_getMagentoInfo();
                $this->_getResponse()->render($data);
                $this->_getResponse()->setHttpResponseCode(Jmango360_Japi_Model_Server::HTTP_OK);
                break;
            case 'getPluginVersion' . Jmango360_Japi_Model_Request::OPERATION_RETRIEVE:
                $data = $this->_getPluginVersion();
                $this->_getResponse()->render($data);
                $this->_getResponse()->setHttpResponseCode(Jmango360_Japi_Model_Server::HTTP_OK);
                break;
            case 'getConfigInfo' . Jmango360_Japi_Model_Request::OPERATION_RETRIEVE:
                $data = $this->_getConfigInfo();
                $this->_getResponse()->render($data);
                $this->_getResponse()->setHttpResponseCode(Jmango360_Japi_Model_Server::HTTP_OK);
                break;
            case 'getDirectoryCountryList' . Jmango360_Japi_Model_Request::OPERATION_RETRIEVE:
                $data = $this->_getDirectoryCountryList();
                $this->_getResponse()->render($data);
                $this->_getResponse()->setHttpResponseCode(Jmango360_Japi_Model_Server::HTTP_OK);
                break;
            case 'getDirectoryRegionList' . Jmango360_Japi_Model_Request::OPERATION_RETRIEVE:
                $data = $this->_getDirectoryRegionList();
                $this->_getResponse()->render($data);
                $this->_getResponse()->setHttpResponseCode(Jmango360_Japi_Model_Server::HTTP_OK);
                break;
            case 'getToken' . Jmango360_Japi_Model_Request::OPERATION_CREATE:
                $data = $this->_getNewToken();
                $this->_getResponse()->render($data);
                $this->_getResponse()->setHttpResponseCode(Jmango360_Japi_Model_Server::HTTP_CREATED);
                break;
            case 'getMagentoModules' . Jmango360_Japi_Model_Request::OPERATION_RETRIEVE:
                $data = $this->_getModules();
                $this->_getResponse()->render($data);
                $this->_getResponse()->setHttpResponseCode(Jmango360_Japi_Model_Server::HTTP_OK);
                break;
            case 'getModuleRewrites' . Jmango360_Japi_Model_Request::OPERATION_RETRIEVE:
                $data = $this->_getRewrites();
                $this->_getResponse()->render($data);
                $this->_getResponse()->setHttpResponseCode(Jmango360_Japi_Model_Server::HTTP_OK);
                break;
            case 'updateTheme' . Jmango360_Japi_Model_Request::OPERATION_CREATE:
                $data = $this->_updateTheme();
                $this->_getResponse()->render($data);
                $this->_getResponse()->setHttpResponseCode(Jmango360_Japi_Model_Server::HTTP_OK);
                break;
            case 'getTheme' . Jmango360_Japi_Model_Request::OPERATION_RETRIEVE:
                $data = $this->_getTheme();
                $this->_getResponse()->render($data);
                $this->_getResponse()->setHttpResponseCode(Jmango360_Japi_Model_Server::HTTP_OK);
                break;
            case 'orders' . Jmango360_Japi_Model_Request::OPERATION_RETRIEVE:
                $data = $this->_getOrders();
                $this->_getResponse()->render($data);
                $this->_getResponse()->setHttpResponseCode(Jmango360_Japi_Model_Server::HTTP_OK);
                break;
            case 'events' . Jmango360_Japi_Model_Request::OPERATION_RETRIEVE:
                $data = $this->_getEvents();
                $this->_getResponse()->render($data);
                $this->_getResponse()->setHttpResponseCode(Jmango360_Japi_Model_Server::HTTP_OK);
                break;
            case 'getSettings' . Jmango360_Japi_Model_Request::OPERATION_RETRIEVE:
                $data = $this->_getSettings();
                $this->_getResponse()->render($data);
                $this->_getResponse()->setHttpResponseCode(Jmango360_Japi_Model_Server::HTTP_OK);
                break;
            case 'getSettingAttributes' . Jmango360_Japi_Model_Request::OPERATION_RETRIEVE:
                $data = $this->_getSettingAttributes();
                $this->_getResponse()->render($data);
                $this->_getResponse()->setHttpResponseCode(Jmango360_Japi_Model_Server::HTTP_OK);
                break;
            case 'getShippingAndPaymentMethods' . Jmango360_Japi_Model_Request::OPERATION_RETRIEVE:
                $data = $this->_getShippingAndPaymentMethods();
                $this->_getResponse()->render($data);
                $this->_getResponse()->setHttpResponseCode(Jmango360_Japi_Model_Server::HTTP_OK);
                break;
            case 'getAllSystemSettings' . Jmango360_Japi_Model_Request::OPERATION_RETRIEVE:
                $data = $this->_getAllSystemSettings();
                $this->_getResponse()->render($data);
                $this->_getResponse()->setHttpResponseCode(Jmango360_Japi_Model_Server::HTTP_OK);
                break;
            case 'saveSettings' . Jmango360_Japi_Model_Request::OPERATION_CREATE:
                $data = $this->_savePluginSettings();
                $this->_getResponse()->render($data);
                $this->_getResponse()->setHttpResponseCode(Jmango360_Japi_Model_Server::HTTP_OK);
                break;
            default:
                throw new Jmango360_Japi_Exception('Resource method not implemented', Jmango360_Japi_Model_Request::HTTP_INTERNAL_ERROR);
                break;
        }
    }

    /**
     * Save Japi system config
     * @return array
     * @throws Jmango360_Japi_Exception
     */
    protected function _savePluginSettings()
    {
        $json = $this->_getRequest()->getParam('data');
        if (stripos($_SERVER['HTTP_HOST'], 'popcorn.nl') !== false) {
            $json = html_entity_decode($json);
        }

        if (!$json) {
            throw new Jmango360_Japi_Exception(
                Mage::helper('japi')->__('Data invalid.'),
                Jmango360_Japi_Model_Request::HTTP_BAD_REQUEST
            );
        }

        try {
            $array = Mage::helper('core')->jsonDecode($json);
        } catch (Exception $e) {
            throw new Jmango360_Japi_Exception(
                $e->getMessage(),
                Jmango360_Japi_Model_Request::HTTP_BAD_REQUEST
            );
        }

        if (!is_array($array) || empty($array['settings'])) {
            throw new Jmango360_Japi_Exception(
                Mage::helper('japi')->__('Data invalid.'),
                Jmango360_Japi_Model_Request::HTTP_BAD_REQUEST
            );
        }

        $groups = array();
        foreach ($array['settings'] as $section) {
            if (!is_array($section)) continue;
            foreach ($section as $group => $fields) {
                if (!isset($groups[$group])) $groups[$group] = array();
                if (!is_array($fields)) continue;
                foreach ($fields as $key => $value) {
                    $groups[$group]['fields'][$key] = array('value' => $value);
                }
            }
        }

        try {
            $section = 'japi';

            $storeId = $this->_getRequest()->getParam('store_id');
            if (is_numeric($storeId) && $storeId > -1) {
                $storeModel = Mage::app()->getStore($storeId);
                if (!$storeModel->getId()) {
                    throw new Jmango360_Japi_Exception(
                        Mage::helper('japi')->__('Store not found.'),
                        Jmango360_Japi_Model_Request::HTTP_BAD_REQUEST
                    );
                }
                $website = $storeModel->getWebsite()->getCode();
                $store = $storeModel->getCode();
            } else {
                $website = null;
                $store = null;
            }

            Mage::getSingleton('adminhtml/config_data')
                ->setSection($section)
                ->setWebsite($website)
                ->setStore($store)
                ->setGroups($groups)
                ->save();

            // reinit configuration
            Mage::getConfig()->reinit();
            Mage::dispatchEvent('admin_system_config_section_save_after', array(
                'website' => $website,
                'store' => $store,
                'section' => $section
            ));
            Mage::app()->reinitStores();

            // website and store codes can be used in event implementation, so set them as well
            Mage::dispatchEvent("admin_system_config_changed_section_{$section}",
                array('website' => $website, 'store' => $store)
            );
            $data['message'][] = Mage::helper('japi')->__('The configuration has been saved.');
        } catch (Exception $e) {
            throw new Jmango360_Japi_Exception(
                $e->getMessage(),
                Jmango360_Japi_Model_Request::HTTP_INTERNAL_ERROR
            );
        }

        return $data;
    }

    /**
     * Get Japi System settings
     * @return array
     */
    protected function _getAllSystemSettings()
    {
        /** @var Jmango360_Japi_Helper_Data $helper */
        $helper = Mage::helper('japi');
        $data = $helper->getPluginSystemConfigs();

        return $data;
    }

    /**
     * Get Japi Setting Attributes
     * @return array
     */
    protected function _getSettingAttributes()
    {
        $data = array();
        /** @var $sourceModel Jmango360_Japi_Model_System_Config_Source_Attributes */
        $sourceModel = Mage::getModel('japi/system_config_source_attributes');
        $data['attributes'] = $sourceModel->toOptionArray();

        return $data;
    }

    protected function _getShippingAndPaymentMethods()
    {
        $data = array();

        /** @var $shippingSource Jmango360_Japi_Model_System_Config_Source_Shipping */
        $shippingSource = Mage::getModel('japi/system_config_source_shipping');
        $data['shipping_methods'] = $shippingSource->toOptionArray();

        /** @var $paymentSource Jmango360_Japi_Model_System_Config_Source_Payment */
        $paymentSource = Mage::getModel('japi/system_config_source_payment');
        $data['payment_methods'] = $paymentSource->toOptionArray();

        return $data;
    }

    protected function _getSettings()
    {
        $storeId = $this->_getRequest()->getParam('store_id');
        $store = Mage::app()->getStore($storeId);
        if (!$store->getId()) {
            throw new Jmango360_Japi_Exception(
                Mage::helper('japi')->__('Store not found.'),
                Jmango360_Japi_Model_Request::HTTP_BAD_REQUEST
            );
        }

        $configFile = Mage::app()->getConfig()->getModuleDir('etc', 'Jmango360_Japi') . DS . 'config.xml';
        /* @var $xml Varien_Simplexml_Element */
        $xml = simplexml_load_string(file_get_contents($configFile), 'Varien_Simplexml_Element');
        $xmlData = $xml->asArray();
        $data = array();

        if (isset($xmlData['default'])) {
            foreach ($xmlData['default'] as $section => $sections) {
                if (!isset($data[$section])) $data[$section] = array();
                foreach ($sections as $group => $groups) {
                    if (in_array($group, $this->_excludeSettingGroups)) continue;
                    if (!isset($data[$section][$group])) $data[$section][$group] = array();
                    foreach ($groups as $field => $value) {
                        $path = sprintf('%s/%s/%s', $section, $group, $field);
                        $data[$section][$group][$field] = Mage::getStoreConfig($path, $store->getId());
                    }
                }
            }
        }

        return array('settings' => $data);
    }

    protected function _getModules()
    {
        $modules = Mage::getConfig()->getNode('modules')->children();
        $output = array();

        foreach ($modules as $name => $module) {
            $output['modules'][] = array(
                'name' => $name,
                'active' => (string)$module->active == 'true' ? true : false,
                'version' => (string)$module->version
            );
        }

        return $output;
    }

    protected function _getEvents()
    {
        $events = $this->_loadEvents();
        $data = array();

        foreach ($events as $eventNodes) {
            foreach ($eventNodes as $eventNode) {
                foreach ($eventNode->children() as $event) {
                    $eventName = $event->getName();
                    if (!isset($data[$eventName])) $data[$eventName] = array();
                    $observers = $event->xpath('observers');
                    foreach ($observers as $observer) {
                        foreach ($observer->children() as $observerNode) {
                            $class = $observerNode->xpath('class');
                            if ($class[0]) {
                                $className = Mage::getConfig()->getModelClassName($class[0]);
                                if (class_exists($className)) {
                                    $class = $className;
                                }
                            }
                            $method = $observerNode->xpath('method');
                            $data[$eventName][] = sprintf('%s:%s', $class, $method[0]);
                        }
                    }
                }
            }
        }

        return array('events' => $data);
    }

    /**
     * Return rewrites data
     */
    protected function _getRewrites()
    {
        $rewrites = $this->_loadRewrites();
        $data['rewrites'] = array();

        foreach ($rewrites as $rewriteNodes) {
            foreach ($rewriteNodes as $node) {
                $nParent = $node->xpath('..');
                $module = (string)$nParent[0]->getName();
                $nSubParent = $nParent[0]->xpath('..');
                $component = (string)$nSubParent[0]->getName();
                if (!in_array($component, array('blocks', 'helpers', 'models'))) {
                    continue;
                }
                $pathNodes = $node->children();
                foreach ($pathNodes as $pathNode) {
                    $path = (string)$pathNode->getName();
                    $completePath = $module . '/' . $path;
                    $rewriteClassName = (string)$pathNode;
                    $instance = Mage::getConfig()->getGroupedClassName(
                        substr($component, 0, -1),
                        $completePath
                    );
                    $data['rewrites'][] = array(
                        'path' => $completePath,
                        'rewrite_class' => $rewriteClassName,
                        'active_class' => $instance,
                        'status' => ($instance == $rewriteClassName)
                    );
                }
            }
        }

        return $data;
    }

    /**
     * Return all rewrites from XML
     *
     * @return array All rwrites
     */
    protected function _loadRewrites()
    {
        $fileName = 'config.xml';
        $modules = Mage::getConfig()->getNode('modules')->children();
        $return = array();
        foreach ($modules as $modName => $module) {
            if ($module->is('active')) {
                $configFile = Mage::getConfig()->getModuleDir('etc', $modName) . DS . $fileName;
                if (file_exists($configFile)) {
                    $xml = file_get_contents($configFile);
                    $xml = simplexml_load_string($xml);
                    if ($xml instanceof SimpleXMLElement) {
                        $return[$modName] = $xml->xpath('//rewrite');
                    }
                }
            }
        }
        return $return;
    }

    protected function _loadEvents()
    {
        $fileName = 'config.xml';
        $modules = Mage::getConfig()->getNode('modules')->children();
        $data = array();

        foreach ($modules as $moduleName => $module) {
            if ($module->is('active')) {
                $configFile = Mage::getConfig()->getModuleDir('etc', $moduleName) . DS . $fileName;
                if (file_exists($configFile)) {
                    $xml = file_get_contents($configFile);
                    $xml = simplexml_load_string($xml);
                    if ($xml instanceof SimpleXMLElement) {
                        $data[$moduleName] = $xml->xpath('//events');
                    }
                }
            }
        }

        return $data;
    }

    protected function _getDirectoryCountryList()
    {
        $collection = Mage::getModel('directory/country')->getCollection();

        $countries = array();
        foreach ($collection as $country) {
            /* @var $country Mage_Directory_Model_Country */
            $country->getName(); // Loading name in default locale
            $countries[] = $country->toArray(array('country_id', 'iso2_code', 'iso3_code', 'name'));
        }

        $data['countries'] = $countries;

        return $data;
    }

    protected function _getDirectoryRegionList()
    {
        $country = $this->_getRequest()->getParam('country_code', null);
        if (is_null($country)) {
            throw new Jmango360_Japi_Exception(Mage::helper('japi')->__('Country code cannot be empty'), Jmango360_Japi_Model_Request::HTTP_INTERNAL_ERROR);
        }

        try {
            /* @var $country Mage_Directory_Model_Country */
            $country = Mage::getModel('directory/country')->loadByCode($country);
        } catch (Mage_Core_Exception $e) {
            throw new Jmango360_Japi_Exception(Mage::helper('japi')->__('Country not exists: ' . $e->getMessage()), Jmango360_Japi_Model_Request::HTTP_INTERNAL_ERROR);
        }

        if (!$country->getId()) {
            throw new Jmango360_Japi_Exception(Mage::helper('japi')->__('Country not exists'), Jmango360_Japi_Model_Request::HTTP_INTERNAL_ERROR);
        }

        $regions = array();
        foreach ($country->getRegions() as $region) {
            /* @var $region Mage_Directory_Model_Region */
            $regionData = $region->toArray(array('region_id', 'code', 'name'));
            $regionData['name'] = $region->getName();
            $regions[] = $regionData;
        }

        $data['regions'] = $regions;

        return $data;
    }

    protected function _getConfigInfo()
    {
        $request = $this->_getRequest();
        $store_id = $request->getParam('store_id', null);
        $store = Mage::app()->getStore($store_id);

        if (!is_object($store) || !$store->getId()) {
            throw new Jmango360_Japi_Exception(Mage::helper('japi')->__('Store not found'), Jmango360_Japi_Model_Request::HTTP_INTERNAL_ERROR);
        }

        Mage::app()->setCurrentStore($store);
        $storeId = $store->getId();

        $data = array();
        $data['store'] = $store->toArray();
        $data['currency'] = $this->getCurrency($storeId);
        $data['general'] = $this->getGeneralInfo($storeId);

        $data['shipping_methods'] = null;
        $data['payment_methods'] = null;

        $data['catalog'] = $this->_getCatalogInfo($storeId);
        $data['signup_options'] = $this->getCommonSignupOptions();
        $data['address_options'] = null;

        return $data;
    }

    public function getCustomerAttributes()
    {
        $attributes = $this->_getCustomerAttributes();
        foreach ($attributes as $k => $attribute) {
            if (in_array($attribute['key'], $this->_excludeCustomerAttributes)) {
                unset($attributes[$k]);
            }
        }
        return $attributes;
    }

    public function getAddressAttributes()
    {
        $attributes = $this->_getAddressAttributes();
        $checkoutAddress = $this->_getCheckoutAddress();
        foreach ($checkoutAddress as $attribute) {
            if (!array_key_exists($attribute['key'], $attributes)) {
                $attributes[$attribute['key']] = $attribute;
            }
        }
        foreach ($attributes as $k => $attribute) {
            if (in_array($attribute['key'], $this->_excludeAddressAttributes) || in_array($attribute['key'], $this->_excludeCustomerAttributes)) {
                unset($attributes[$k]);
            }
        }
        return $attributes;
    }

    protected function _getCheckoutAddress()
    {
        /** @var $customerForm Mage_Customer_Model_Form */
        $customerForm = Mage::getModel('customer/form');
        $customerForm->setFormCode('checkout_register');
        /* @var $customer Mage_Customer_Model_Customer */
        $customer = Mage::getModel('customer/customer');
        $customerForm->setEntity($customer);
        $attributes = array();
        foreach ($customerForm->getAttributes() as $attribute) {
            if ($item = $this->_processAttribute($attribute)) {
                $attributes[$item['key']] = $item;
            }
        }
        return $attributes;
    }

    protected function _getCustomerAttributes($storeId = null)
    {
        $attributes = array();
        /* @var $customerForm Mage_Customer_Model_Form */
        $customerForm = Mage::getModel('customer/form');
        $customerForm->setFormCode('customer_account_create');
        foreach ($customerForm->getAttributes() as $attribute) {
            if ($item = $this->_processAttribute($attribute)) {
                $attributes[$item['key']] = $item;
            }
        }
        return $attributes;
    }

    protected function _getAddressAttributes($storeId = null)
    {
        $attributes = array();
        /* @var $addressForm Mage_Customer_Model_Form */
        $address = Mage::getModel('customer/address');
        $addressForm = Mage::getModel('customer/form');
        $addressForm->setFormCode('customer_register_address');
        $addressForm->setEntity($address);
        foreach ($addressForm->getAttributes() as $attribute) {
            if ($item = $this->_processAttribute($attribute)) {
                if ($item['key'] == 'street') {
                    $item['display_type'] = 'multi_line';
                    $lines = Mage::helper('customer/address')->getStreetLines();
                    $lines = $lines < 0 ? 1 : $lines;
                    for ($i = 1; $i <= $lines; $i++) {
                        $item['options'][$i] = $item['label'] . ' ' . $i;
                    }
                    $attributes[$item['key']] = $item;
                } else {
                    $attributes[$item['key']] = $item;
                }
            }
        }
        return $attributes;
    }

    protected function _getAdditionalAttributes($storeId = null)
    {
        $attributes = array();

        if (Mage::helper('core')->isModuleOutputEnabled('Mage_Newsletter') && !in_array('is_subscribed', $this->_attributes)) {
            $attributes['is_subscribed'] = array(
                'key' => 'is_subscribed',
                'label' => Mage::helper('japi')->__('Sign Up for Newsletter'),
                'enable' => true,
                'display_type' => 'checkbox',
                'required' => false
            );
        }

        if (!in_array('password', $this->_attributes)) {
            $attributes['password'] = array(
                'key' => 'password',
                'label' => Mage::helper('japi')->__('Password'),
                'enable' => true,
                'display_type' => 'password',
                'required' => true
            );
        }

        if (!in_array('confirmation', $this->_attributes)) {
            $attributes['confirmation'] = array(
                'key' => 'confirmation',
                'label' => Mage::helper('japi')->__('Confirm Password'),
                'enable' => true,
                'display_type' => 'password',
                'required' => true
            );
        }

        return $attributes;
    }

    /**
     * @param Mage_Customer_Model_Customer|null $customer
     * @return array
     */
    public function getCommonSignupOptions($customer = null)
    {
        $attributes = array();

        /* @var $nameWidget Mage_Customer_Block_Widget_Name */
        $nameWidget = Mage::app()->getLayout()->createBlock('customer/widget_name');
        if ($nameWidget) {
            $nameWidget->setForceUseCustomerAttributes(true);

            if ($nameWidget->showPrefix()) {
                $prefix = array(
                    'label' => $nameWidget->getStoreLabel('prefix'),
                    'key' => 'prefix',
                    'enable' => true,
                    'required' => $nameWidget->isPrefixRequired()
                );
                if ($nameWidget->getPrefixOptions() === false) {
                    $prefix['display_type'] = 'field';
                } else {
                    $prefix['display_type'] = 'drop_down';
                    foreach ($nameWidget->getPrefixOptions() as $option) {
                        $prefix['options'][$option] = Mage::helper('customer')->__($option);
                    }
                }
                $attributes[] = $prefix;
            }

            $attributes[] = array(
                'label' => $nameWidget->getStoreLabel('firstname') ? $nameWidget->getStoreLabel('firstname') : Mage::helper('customer')->__('First Name'),
                'key' => 'firstname',
                'enable' => true,
                'required' => true,
                'display_type' => 'field'
            );

            if ($nameWidget->showMiddlename()) {
                $attributes[] = array(
                    'label' => $nameWidget->getStoreLabel('middlename'),
                    'key' => 'middlename',
                    'enable' => true,
                    'required' => $nameWidget->isMiddlenameRequired(),
                    'display_type' => 'field'
                );
            }

            $attributes[] = array(
                'label' => $nameWidget->getStoreLabel('lastname') ? $nameWidget->getStoreLabel('lastname') : Mage::helper('customer')->__('Last Name'),
                'key' => 'lastname',
                'enable' => true,
                'required' => true,
                'display_type' => 'field'
            );

            if ($nameWidget->showSuffix()) {
                $suffix = array(
                    'label' => $nameWidget->getStoreLabel('suffix'),
                    'key' => 'suffix',
                    'enable' => true,
                    'required' => $nameWidget->isSuffixRequired()
                );
                if ($nameWidget->getSuffixOptions() === false) {
                    $suffix['display_type'] = 'field';
                } else {
                    $suffix['display_type'] = 'drop_down';
                    foreach ($nameWidget->getSuffixOptions() as $option) {
                        $suffix['options'][$option] = Mage::helper('customer')->__($option);
                    }
                }
                $attributes[] = $suffix;
            }
        }

        $attributes[] = array(
            'label' => Mage::helper('customer')->__('Email Address'),
            'key' => 'email',
            'enable' => true,
            'required' => true,
            'display_type' => 'field'
        );

        if (Mage::helper('core')->isModuleOutputEnabled('Mage_Newsletter')) {
            $attributes[] = array(
                'label' => Mage::helper('customer')->__('Sign Up for Newsletter'),
                'key' => 'is_subscribed',
                'enable' => true,
                'display_type' => 'checkbox',
                'required' => false
            );
        }

        /* @var $dobWidget Mage_Customer_Block_Widget_Dob */
        $dobWidget = Mage::app()->getLayout()->createBlock('customer/widget_dob');
        if ($dobWidget->isEnabled()) {
            $attributes[] = array(
                'label' => Mage::helper('customer')->__('Date of Birth'),
                'key' => 'dob',
                'enable' => true,
                'required' => $dobWidget->isRequired(),
                'display_type' => 'date'
            );
        }

        /* @var $taxWidget Mage_Customer_Block_Widget_Taxvat */
        $taxWidget = Mage::app()->getLayout()->createBlock('customer/widget_taxvat');
        if ($taxWidget->isEnabled()) {
            $attributes[] = array(
                'label' => Mage::helper('customer')->__('Tax/VAT number'),
                'key' => 'taxvat',
                'enable' => true,
                'required' => $taxWidget->isRequired(),
                'display_type' => 'field'
            );
        }

        /* @var $genderWidget Mage_Customer_Block_Widget_Gender */
        $genderWidget = Mage::app()->getLayout()->createBlock('customer/widget_gender');
        if ($genderWidget->isEnabled()) {
            $gender = array(
                'label' => Mage::helper('customer')->__('Gender'),
                'key' => 'gender',
                'enable' => true,
                'required' => $genderWidget->isRequired(),
                'display_type' => 'drop_down'
            );
            $options = Mage::getResourceSingleton('customer/customer')->getAttribute('gender')->getSource()->getAllOptions();
            foreach ($options as $option) {
                $gender['options'][$option['value']] = $option['label'];
            }
            $attributes[] = $gender;
        }

        if (!$customer) {
            $attributes[] = array(
                'label' => Mage::helper('customer')->__('Password'),
                'key' => 'password',
                'enable' => true,
                'required' => true,
                'display_type' => 'password'
            );

            $attributes[] = array(
                'label' => Mage::helper('customer')->__('Confirm Password'),
                'key' => 'confirmation',
                'enable' => true,
                'required' => true,
                'display_type' => 'password'
            );
        }

        if (strpos(Mage::getUrl(), 'voordeelwijnen.nl')) {
            $attributes[] = array(
                'label' => Mage::helper('japi')->__('Ja, ik ben 18 jaar of ouder.'),
                'key' => '16year',
                'enable' => true,
                'required' => true,
                'display_type' => 'checkbox'
            );
        }

        if ($customer && $customer->getId()) {
            foreach ($attributes as $key => $attribute) {
                if (!empty($attribute['key'])) {
                    if ($attribute['key'] == 'is_subscribed') {
                        /* @var $subscriptionModel Mage_Newsletter_Model_Subscriber */
                        $subscriptionModel = Mage::getModel('newsletter/subscriber');
                        if ($subscriptionModel) {
                            $subscriptionModel->loadByCustomer($customer);
                            $attribute['value'] = $subscriptionModel->isSubscribed();
                        }
                    } else {
                        $attribute['value'] = $customer->getData($attribute['key']);
                    }
                    $attributes[$key] = $attribute;
                }
            }
        }

        return $attributes;
    }

    protected function _getSignupOptions($storeId = null)
    {
        $attributes = $this->_getCustomerAttributes($storeId);
        $includeCustomerAttributes = explode(',', Mage::getStoreConfig('japi/jmango_rest_customer_settings/attributes'));
        foreach ($attributes as $k => $attribute) {
            if (!in_array($attribute['key'], $this->_excludeCustomerAttributes) && !in_array($attribute['key'], $includeCustomerAttributes)) {
                unset($attributes[$k]);
            }
        }

        if (Mage::getStoreConfigFlag('japi/jmango_rest_customer_settings/enable_address')) {
            $addressAttributes = $this->_getAddressAttributes($storeId);
            $includeAddressAttributes = explode(',', Mage::getStoreConfig('japi/jmango_rest_customer_settings/address_attributes'));
            foreach ($addressAttributes as $k => $attribute) {
                if (!in_array($attribute['key'], $this->_excludeAddressAttributes) && !in_array($attribute['key'], $includeAddressAttributes)) {
                    unset($attributes[$k]);
                }
            }
        }

        $additinalAttributes = $this->_getAdditionalAttributes($storeId);
        foreach ($additinalAttributes as $attribute) {
            $includeAttributes[] = $attribute['key'];
            if (!array_key_exists($attribute['key'], $attributes)) {
                $attributes[$attribute['key']] = $attribute;
            }
        }

        return array_values($attributes);
    }

    public function getAddressField()
    {
        return $this->_getAddressOptions();
    }

    protected function _getAddressOptions($storeId = null)
    {
        $attributes = $this->_getAddressAttributes($storeId);
        $includeAttributes = explode(',', Mage::getStoreConfig('japi/jmango_rest_customer_settings/address_attributes'));
        foreach ($attributes as $k => $attribute) {
            if (!in_array($attribute['key'], $this->_excludeAddressAttributes) && !in_array($attribute['key'], $includeAttributes)) {
                unset($attributes[$k]);
            }
        }

        return array_values($attributes);
    }

    /**
     * @param $attribute $attribute Mage_Customer_Model_Attribute
     * @return array|null
     */
    protected function _processAttribute($attribute)
    {
        /* @var $eavConfig Mage_Eav_Model_Config */
        $eavConfig = Mage::getSingleton('eav/config');
        /* @var $helper Jmango360_Japi_Helper_Data */
        $helper = Mage::helper('japi');

        try {
            /* @var $attribute Mage_Customer_Model_Attribute */
            $attributeCode = $attribute->getAttributeCode();
            if (!in_array($attributeCode, $this->_attributes)) {
                $this->_attributes[] = $attributeCode;
            }
            /* @var $entityType Mage_Eav_Model_Entity_Type */
            $entityType = $attribute->getEntityType();
            $attributeLabel = $helper->__(
                $eavConfig->getAttribute($entityType->getEntityTypeCode(), $attributeCode)->getStoreLabel()
            );

            switch ($attribute->getFrontendInput()) {
                case 'select':
                    $item = array(
                        'key' => $attributeCode,
                        'display_type' => 'drop_down',
                        'label' => $attributeLabel,
                        'enable' => true,
                        'required' => (bool)$attribute->getIsRequired()
                    );
                    if ($attribute->getSource()) {
                        foreach ($attribute->getSource()->getAllOptions() as $option) {
                            $item['options'][$option['value']] = $helper->__($option['label']);
                        }
                    }
                    break;
                case 'text':
                    $item = array(
                        'key' => $attributeCode,
                        'display_type' => 'field',
                        'label' => $attributeLabel,
                        'enable' => true,
                        'required' => (bool)$attribute->getIsRequired()
                    );
                    break;
                case 'boolean':
                    $item = array(
                        'key' => $attributeCode,
                        'display_type' => 'checkbox',
                        'label' => $attributeLabel,
                        'enable' => true,
                        'required' => (bool)$attribute->getIsRequired()
                    );
                    break;
                case 'date':
                    $item = array(
                        'key' => $attributeCode,
                        'display_type' => 'date',
                        'label' => $attributeLabel,
                        'enable' => true,
                        'required' => (bool)$attribute->getIsRequired()
                    );
                    break;
                case 'multiline':
                    $item = array(
                        'key' => $attributeCode,
                        'display_type' => 'multi_line',
                        'label' => $attributeLabel,
                        'enable' => true,
                        'required' => (bool)$attribute->getIsRequired()
                    );
                    break;
                case 'multiselect':
                    $item = array(
                        'key' => $attributeCode,
                        'display_type' => 'multi_select',
                        'label' => $attributeLabel,
                        'enable' => true,
                        'required' => (bool)$attribute->getIsRequired()
                    );
                    if ($attribute->getSource()) {
                        foreach ($attribute->getSource()->getAllOptions() as $option) {
                            $item['options'][$option['value']] = $helper->__($option['label']);
                        }
                    }
                    break;
                default:
                    $item = array(
                        'key' => $attributeCode,
                        'display_type' => 'field',
                        'label' => $attributeLabel,
                        'enable' => true,
                        'required' => (bool)$attribute->getIsRequired()
                    );
            }

            /* @var $nameBlock Mage_Customer_Block_Widget_Name */
            $nameBlock = $helper->getBlock('customer/widget_name');
            $nameBlock->setForceUseCustomerAttributes(true);
            switch ($attributeCode) {
                case 'prefix':
                    if (!$nameBlock->showPrefix()) {
                        return null;
                    }
                    if ($nameBlock->getPrefixOptions()) {
                        $item['display_type'] = 'drop_down';
                        foreach ($nameBlock->getPrefixOptions() as $option) {
                            $item['options'][$option] = $option;
                        }
                    }
                    break;
                case 'middlename':
                    if (!$nameBlock->showMiddlename()) {
                        return null;
                    }
                    break;
                case 'suffix':
                    if (!$nameBlock->showSuffix()) {
                        return null;
                    }
                    if ($nameBlock->getSuffixOptions()) {
                        $item['display_type'] = 'drop_down';
                        foreach ($nameBlock->getSuffixOptions() as $option) {
                            $item['options'][$option] = $option;
                        }
                    }
                    break;
                case 'dob':
                    /* @var $dobBlock Mage_Customer_Block_Widget_Dob */
                    $dobBlock = $helper->getBlock('customer/widget_dob');
                    if (!$dobBlock->isEnabled()) {
                        return null;
                    }
                    break;
                case 'taxvat':
                    /* @var $taxBlock Mage_Customer_Block_Widget_Taxvat */
                    $taxBlock = $helper->getBlock('customer/widget_taxvat');
                    if (!$taxBlock->isEnabled()) {
                        return null;
                    }
                    break;
                case 'gender':
                    /* @var $genderBlock Mage_Customer_Block_Widget_Gender */
                    $genderBlock = $helper->getBlock('customer/widget_gender');
                    if (!$genderBlock->isEnabled()) {
                        return null;
                    }
                    break;
            }

            return $item;
        } catch (Exception $e) {
            Mage::logException($e);
        }

        return null;
    }

    protected function getCurrency($storeId)
    {
        $data = array();
        $data['base'] = Mage::getStoreConfig(self::PATH_CURRENCY_BASE, $storeId);
        $data['default'] = Mage::getStoreConfig(self::PATH_CURRENCY_DEFAULT, $storeId);
        $data['allow'] = Mage::getStoreConfig(self::PATH_CURRENCY_ALLOW, $storeId);

        return $data;
    }

    protected function getGeneralInfo($storeId)
    {
        $data = array();

        /* @var $coreHelper Mage_Core_Helper_Data */
        $coreHelper = Mage::helper('core');
        if ($coreHelper->isModuleEnabled('Idev_OneStepCheckout') && $coreHelper->isModuleOutputEnabled('Idev_OneStepCheckout')) {
            $data['default_country'] = Mage::getStoreConfig(self::PATH_IDEV_DEFAULT_COUNTRY, $storeId);
            if (!$data['default_country']) {
                $data['default_country'] = Mage::getStoreConfig(self::PATH_DEFAULT_COUNTRY, $storeId);
            }
        } else {
            $data['default_country'] = Mage::getStoreConfig(self::PATH_DEFAULT_COUNTRY, $storeId);
        }
        $data['allow_countries'] = Mage::getStoreConfig(self::PATH_COUNTRY_ALLOW, $storeId);
        $data['optional_zip_countries'] = Mage::getStoreConfig(self::PATH_OPTIONAL_POSTCODE, $storeId);
        $data['state_required'] = Mage::getStoreConfig(self::PATH_STATE_REQUIRED, $storeId);
        $data['display_not_required_state'] = Mage::getStoreConfig(self::PATH_DISPLAY_ALL, $storeId);
        $data['timezone'] = Mage::getStoreConfig(self::PATH_TIMEZONE, $storeId);
        $data['locale'] = Mage::getStoreConfig(self::PATH_LOCALE, $storeId);
        $data['guest_checkout'] = Mage::getStoreConfig(self::PATH_GUEST_CHECKOUT, $storeId);

        // Check wishlist available
        /* @var $helper Mage_Wishlist_Helper_Data */
        $helper = Mage::helper('wishlist');
        $data['enable_wishlist'] = $helper->isAllow() ? 1 : 0;

        return $data;
    }

    protected function _getPluginVersion()
    {
        $class = get_class($this);
        $parts = explode('_', $class);
        $module = ucfirst($parts[0]) . '_' . ucfirst($parts[1]);

        $data['result'] = (string)Mage::getConfig()->getNode('modules')->$module->version;

        return $data;
    }

    protected function _getMagentoInfo()
    {
        $data['magento_version'] = Mage::getVersion();
        $data['magento_edition'] = version_compare(Mage::getVersion(), '1.7.0', '<') ? 'Community' : Mage::getEdition();

        return $data;
    }

    protected function _getNewSessionId()
    {
        $this->_validateRestApiUser();

        /* @var $session Mage_Core_Model_Session */
        $session = Mage::getSingleton('core/session');
        $data['session_id'] = $session->getSessionId();

        return $data;
    }

    protected function _getNewToken()
    {
        $this->_validateRestApiUser();

        /*
         * Token is auto added in the server response
         */
        $data = array();

        return $data;
    }

    protected function _validateRestApiUser()
    {
        /*
         * @TODO: Maybe move the user test to a rest user class or symply to a japi helper
         *   -- After decided on how complex or simple the user check is going to be
         */
        $request = $this->_getRequest();
        $requestApiUser = $request->getParam('api_user', null);
        $requestApiKey = $request->getParam('api_key', null);
        $systemApiUser = Mage::getStoreConfig(self::APIUSERPATH);
        $systemApiKey = Mage::getStoreConfig(self::APIKEYPATH);
        if ($requestApiUser != $systemApiUser || $requestApiKey != $systemApiKey) {
            throw new Jmango360_Japi_Exception(Mage::helper('japi')->__('Not allowed.'), Jmango360_Japi_Model_Request::HTTP_INTERNAL_ERROR);
        }

        return true;
    }

    protected function _store($set = false)
    {
        $data = array();

        if ($set) {
            $option = $this->_getRequest()->getParam('option', null);
            if (!empty($option)) {
                switch ($option) {
                    case 'set_current_store':
                        $data = $this->_setCurrentStore();
                        break;
                    default:
                        throw new Jmango360_Japi_Exception(Mage::helper('japi')->__('Set store option %s not found', $option), Jmango360_Japi_Model_Request::HTTP_INTERNAL_ERROR);
                }
            } else {
                throw new Jmango360_Japi_Exception(Mage::helper('japi')->__('Set store option cannot be empty.'), Jmango360_Japi_Model_Request::HTTP_INTERNAL_ERROR);
            }
        }

        $storeData = Mage::app()->getStore()->getData();
        $storeData['store_url'] = Mage::getStoreConfig('web/unsecure/base_url');
        $storeData['root_category_id'] = Mage::app()->getStore()->getRootCategoryId();
        $storeData['is_default'] = Mage::app()->getStore()->getWebsite()->getDefaultStore()->getId() == Mage::app()->getStore()->getId();

        $data['current'] = $storeData;
        $data['list'] = array();

        foreach (Mage::app()->getStores() as $storeId => $store) {
            /* @var $store Mage_Core_Model_Store */
            $d = $store->getData();
            $d['store_url'] = Mage::getStoreConfig('web/unsecure/base_url', $store);
            $d['root_category_id'] = $store->getRootCategoryId();
            $d['is_default'] = Mage::app()->getStore($storeId)->getWebsite()->getDefaultStore()->getId() == $storeId;

            $data['list'][] = $d;
        }

        return $data;
    }

    protected function _setCurrentStore($data = array())
    {
        $storeId = $this->_getRequest()->getParam('store_id', null);
        if (!is_null($storeId)) {
            Mage::app()->setCurrentStore($storeId);
            $data['store_url'] = Mage::getUrl(null, array('_nosid' => true));
            $data['store_switch'] = '___store=' . Mage::app()->getStore()->getCode();
        }

        return $data;
    }

    protected function _getRequest()
    {
        return $this->_getServer()->getRequest();
    }

    protected function _getResponse()
    {
        return $this->_getServer()->getResponse();
    }

    /**
     * @return Jmango360_Japi_Model_Server
     */
    protected function _getServer()
    {
        return Mage::getSingleton('japi/server');
    }

    protected function _getCatalogInfo($storeId)
    {
        $data = array();
        $data['show_stock_info'] = Mage::getStoreConfigFlag(self::PATH_SHOW_STOCK_INFO, $storeId) ? 1 : 0;

        return $data;
    }

    protected function _updateTheme()
    {
        $this->_validateRestApiUser();

        $data = $this->_getRequest()->getRawBody();

        $config = Mage::app()->getConfig();
        $config->saveConfig(self::PATH_THEME_CONFIG, $data);
        $cache = Mage::app()->getCache();
        $cache->save($data, 'japi_checkout_theme', array(Mage_Core_Model_Config::CACHE_TAG), null);

        $out = array('success' => true, 'message' => 'OK');
        return $out;
    }

    protected function _getTheme()
    {
        $cache = Mage::app()->getCache();
        $data = $cache->load('japi_checkout_theme');
        if (!$data) {
            $data = Mage::getStoreConfig(self::PATH_THEME_CONFIG, 0);
        }

        return array('data' => $data);
    }

    public function getThemeData()
    {
        $result = $this->_getTheme();
        return isset($result['data']) ? $result['data'] : '{}';
    }

    protected function _getOrders()
    {
        /* @var $model Jmango360_Japi_Model_Rest_Customer_Order_List */
        $model = Mage::getModel('japi/rest_customer_order_list');
        $data = $model->getJapiOrders();
        return $data;
    }
}