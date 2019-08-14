<?php

class Jmango360_Japi_Helper_Data extends Mage_Core_Helper_Abstract
{
    const USESHOPDESIGNSETTINGSPATH = 'japi/jmango_rest_design_settings/use_shopdesign_setting';
    const DEFAULTRESTPACKAGEPATH = 'japi/jmango_rest_design_settings/mobile_package_name';
    const DEFAULTRESTTHEMEPATH = 'japi/jmango_rest_design_settings/mobile_theme_name';
    const DEFAULTRESTPACKAGE = 'default';
    const DEFAULTRESTTHEME = 'default';

    protected $DEFAULT_CUSTOMER_ATTRIBUTES = array(
        'prefix', 'firstname', 'middlename', 'lastname', 'suffix', 'email', 'dob', 'taxvat', 'gender'
    );
    protected $DEFAULT_CUSTOMER_ADDRESS_ATTRIBUTES = array(
        'prefix', 'firstname', 'middlename', 'lastname', 'suffix', 'company', 'street', 'city', 'country_id',
        'region', 'region_id', 'postcode', 'telephone', 'fax', 'vat_id'
    );

    protected $systemConfigPathExcludes = array(
        'jmango_rest_api',
        'jmango_rest_developer_settings'
    );

    protected $_filters;

    /**
     * Support Mana Filters
     */
    public function isFilterEnabled($filter, $block)
    {
        if (!$this->isModuleEnabled('Mana_Filters')) return true;

        $attributeCode = $filter->getAttributeModel() ? $filter->getAttributeModel()->getAttributeCode() : '';
        if (!$attributeCode) return false;

        if (!$this->_filters) {
            /* @var $manaFiltersHelper Mana_Filters_Helper_Data */
            $manaFiltersHelper = Mage::helper('mana_filters');
            /* @var $manaCoreHelper Mana_Core_Helper_Data */
            $manaCoreHelper = Mage::helper('mana_core');

            $request = Mage::app()->getRequest();

            if ($request->getModuleName() == 'catalogsearch' && $request->getActionName() == 'search') {
                $manaFiltersHelper->setMode('search');
                $_filterOptionsCollection = Mage::getResourceModel('mana_filters/filter2_store_collection')
                    ->addColumnToSelect('*')
                    ->addStoreFilter(Mage::app()->getStore())
                    ->setOrder('position', 'ASC');
                Mage::dispatchEvent('m_before_load_filter_collection', array('collection' => $_filterOptionsCollection));
            } else {
                $setIds = Mage::getSingleton('catalog/layer')->getProductCollection()->getSetIds();
                $_filterOptionsCollection = Mage::getResourceModel('mana_filters/filter2_store_collection')
                    ->addFieldToSelect('*')
                    ->addCodeFilter($this->_getAttributeCodes($setIds))
                    ->addStoreFilter(Mage::app()->getStore())
                    ->setOrder('position', 'ASC');
                Mage::dispatchEvent('m_before_load_filter_collection', array('collection' => $_filterOptionsCollection));
            }

            foreach ($_filterOptionsCollection as $filterOptions) {
                /* @var $filterOptions Mana_Filters_Model_Filter2_Store */
                if ($manaFiltersHelper->isFilterEnabled($filterOptions)
                    && (!(method_exists($manaCoreHelper, 'isManadevDependentFilterInstalled') && $manaCoreHelper->isManadevDependentFilterInstalled()) || !Mage::helper('manapro_filterdependent')->hide($filterOptions, $_filterOptionsCollection))
                    && $manaFiltersHelper->canShowFilterInBlock($block, $filterOptions)
                ) {
                    $this->_filters[] = $filterOptions->getCode();
                }
            }
        }

        if (is_array($this->_filters)) {
            return in_array($attributeCode, $this->_filters);
        }

        return true;
    }

    protected function _getAttributeCodes($setIds)
    {
        /* @var $collection Mage_Catalog_Model_Resource_Eav_Mysql4_Product_Attribute_Collection */
        $collection = Mage::getResourceModel('catalog/product_attribute_collection');
        $collection->setAttributeSetFilter($setIds);
        $select = $collection->getSelect()
            ->reset(Zend_Db_Select::COLUMNS)
            ->columns('attribute_code');

        return array_merge($collection->getConnection()->fetchCol($select), array('category'));
    }

    /**
     * Check if we should wrap payment methods block html
     */
    public function wrapPaymentBlockMethods()
    {
        return version_compare(Mage::getVersion(), '1.8', '<') === true
            || $this->isModuleEnabled('AW_Points');
    }

    /**
     * Set session cookie if website not accept SID
     */
    public function checkValidSession()
    {
        $SID = Mage::app()->getRequest()->getParam('SID');
        $sid = Mage::getSingleton('core/session')->getSessionId();
        if ($SID != $sid) {
            setcookie(session_name(), $SID, time() + Mage::app()->getCookie()->getLifetime(), '/');
        }
    }

    /**
     * @param $form Mage_Eav_Model_Form
     * @param $excluded array
     * @return array
     */
    protected function _getFormAttributes($form, $excluded = array())
    {
        if (!$form) return array();

        /* @var $eavConfig Mage_Eav_Model_Config */
        $eavConfig = Mage::getSingleton('eav/config');

        $attributes = array();
        foreach ($form->getAttributes() as $attribute) {
            $attributeCode = $attribute->getAttributeCode();
            if (in_array($attributeCode, $excluded)) continue;
            $entityType = $attribute->getEntityType();
            $data = array(
                'key' => $attributeCode,
                'label' => $eavConfig->getAttribute($entityType->getEntityTypeCode(), $attributeCode)->getStoreLabel(),
                'display_type' => $attribute->getFrontendInput(),
                'required' => (bool)$attribute->getIsRequired()
            );
            try {
                foreach ($attribute->getSource()->getAllOptions() as $option) {
                    $data['options'][$option['value']] = $this->__($option['label']);
                }
            } catch (Exception $e) {
            }
            $attributes[] = $data;
        }

        return $attributes;
    }

    /**
     * Get additional fields on signup form
     */
    public function getSignupFormFields()
    {
        /* @var $customerForm Mage_Customer_Model_Form */
        $customerForm = Mage::getModel('customer/form');
        /* @var $customer Mage_Customer_Model_Customer */
        $customer = Mage::getModel('customer/customer');
        $customerForm->setFormCode('customer_account_create');
        $customerForm->setEntity($customer);

        return $this->_getFormAttributes($customerForm, $this->DEFAULT_CUSTOMER_ATTRIBUTES);
    }

    /**
     * Get additional fields on signup address form
     */
    public function getSignupAddressFormFields()
    {
        /* @var $address Mage_Customer_Model_Address */
        $address = Mage::getModel('customer/address');
        /* @var $addressForm Mage_Customer_Model_Form */
        $addressForm = Mage::getModel('customer/form');
        $addressForm->setFormCode('customer_register_address')
            ->setEntity($address);

        return $this->_getFormAttributes($addressForm, $this->DEFAULT_CUSTOMER_ADDRESS_ATTRIBUTES);
    }

    /**
     * Get additional fields on customer address form
     */
    public function getCustomerAddressFormFields()
    {
        /* @var $address Mage_Customer_Model_Address */
        $address = Mage::getModel('customer/address');
        /* @var $addressForm Mage_Customer_Model_Form */
        $addressForm = Mage::getModel('customer/form');
        $addressForm->setFormCode('customer_address_edit')
            ->setEntity($address);

        return $this->_getFormAttributes($addressForm, $this->DEFAULT_CUSTOMER_ADDRESS_ATTRIBUTES);
    }

    /**
     * Get additional fields on checkout address form
     */
    public function getCheckoutAddressFormFields()
    {
        /* @var $customer Mage_Customer_Model_Customer */
        $customer = Mage::getModel('customer/customer');
        /* @var $addressForm Mage_Customer_Model_Form */
        $addressForm = Mage::getModel('customer/form');
        $addressForm->setFormCode('checkout_register')
            ->setEntity($customer);

        return $this->_getFormAttributes($addressForm, array_merge(
            $this->DEFAULT_CUSTOMER_ADDRESS_ATTRIBUTES,
            $this->DEFAULT_CUSTOMER_ATTRIBUTES
        ));
    }

    /**
     * Get extension version
     */
    public function getExtensionVersion($module)
    {
        try {
            if (!$module) return null;
            return (string)Mage::getConfig()->getNode()->modules->$module->version;
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Check if column "japi" exist in table "sales_flat_order"
     */
    public function hasJapiOrderData()
    {
        /* @var $resource Mage_Core_Model_Resource */
        $resource = Mage::getSingleton('core/resource');
        $connection = $resource->getConnection('core_read');
        return $connection->tableColumnExists($resource->getTableName('sales/order'), 'japi');
    }

    /**
     * Check if customer entity has "japi" attribute
     */
    public function hasJapiCustomerData()
    {
        /* @var $setup Mage_Eav_Model_Entity_Setup */
        $setup = new Mage_Eav_Model_Entity_Setup('core_setup');
        return $setup->getAttribute('customer', 'japi');
    }

    /**
     * Check if product entity has "hide_in_jm360" attribute
     */
    public function hasJapiProductData()
    {
        /* @var $setup Mage_Eav_Model_Entity_Setup */
        $setup = new Mage_Eav_Model_Entity_Setup('core_setup');
        return $setup->getAttribute(Mage_Catalog_Model_Product::ENTITY, 'hide_in_jm360');
    }

    /**
     * @param Mage_Sales_Model_Quote $quote
     * @return array
     */
    public function getTotals($quote = null)
    {
        /* @var $quote Mage_Sales_Model_Quote */
        $quote = $quote ? $quote : Mage::getSingleton('checkout/session')->getQuote();
        $totals = $quote->getTotals();
        /* @var $taxConfig Mage_Tax_Model_Config */
        $taxConfig = Mage::getSingleton('tax/config');
        /* @var $taxHelper Mage_Tax_Helper_Data */
        $taxHelper = Mage::helper('tax');

        $rows = array();

        if (strpos(Mage::getBaseUrl(), 'luckylight') !== false) {
            if ($quote->getShippingAddress()->getShippingMethod() != "pickupatstore_1") {
                $subtotal = 0;
                $tax = 0;
                $discount = 0;
                $discountTitle = '';
                foreach ($totals as $total) {
                    if ($total->getCode() == 'subtotal') {
                        $subtotal = $total->getValueExclTax();
                    }
                    if ($total->getCode() == 'tax') {
                        $tax = $total->getValue();
                    }
                    if ($total->getCode() == 'discount') {
                        $discount = $total->getValue();
                        $discountTitle = $total->getTitle();
                    }
                }
                if ($subtotal) {
                    $shipping = $subtotal >= 100 ? "0" : "4.75";
                    $subtotalExclTax = function_exists('bcadd') ? bcadd($subtotal, $shipping, 4) : $subtotal + $shipping;
                    $shippingInclTax = function_exists('bcmul') ? bcmul($shipping, 1.21, 4) : $shipping * 1.21;
                    $shippingExclTax = function_exists('bcsub') ? bcsub($shippingInclTax, $shipping, 4) : $shippingInclTax - $shipping;
                    $taxInclTax = function_exists('bcadd') ? bcadd($shippingExclTax, $tax, 4) : $shippingExclTax + $tax;
                    $grandTotalExclTax = function_exists('bcadd') ? bcadd($subtotalExclTax, $discount, 4) : $subtotalExclTax + $discount;
                    $grandTotalInclTax = function_exists('bcmul') ? bcmul($grandTotalExclTax, 1.21, 4) : $grandTotalExclTax * 1.21;

                    $rows[] = array(
                        'title' => 'Verzendkosten',
                        'code' => 'shipping',
                        'value' => $shipping
                    );
                    $rows[] = array(
                        'title' => 'Totaal excl. btw',
                        'code' => 'subtotal',
                        'value' => $subtotalExclTax
                    );
                    if ($discount) {
                        $rows[] = array(
                            'title' => $discountTitle,
                            'code' => 'discount',
                            'value' => $discount
                        );
                    }
                    $rows[] = array(
                        'title' => 'BTW',
                        'code' => 'tax',
                        'value' => $taxInclTax
                    );
                    $rows[] = array(
                        'title' => 'Totaalprijs incl. btw',
                        'code' => 'grand_total_incl',
                        'value' => $grandTotalInclTax
                    );

                    return $rows;
                }
            }
        }

        foreach ($totals as $total) {
            /* @var $total Mage_Sales_Model_Quote_Address_Total_Abstract */

            /**
             * Fix for MPLUGIN-665
             * Not add total to return data if title or value is null
             */
            if ($total->getTitle() === null || $total->getValue() === null) {
                continue;
            }

            switch ($total->getCode()) {
                case 'shipping':
                    if ($taxConfig->displayCartShippingBoth()) {
                        $rows[] = array(
                            'title' => $taxHelper->__('Shipping Excl. Tax (%s)', $total->getAddress()->getShippingDescription()),
                            'code' => $total->getCode(),
                            'value' => $total->getAddress()->getShippingAmount()
                        );
                        $rows[] = array(
                            'title' => $taxHelper->__('Shipping Incl. Tax (%s)', $total->getAddress()->getShippingDescription()),
                            'code' => $total->getCode() . '_incl',
                            'value' => $total->getAddress()->getShippingInclTax()
                        );
                    } elseif ($taxConfig->displayCartShippingInclTax()) {
                        $rows[] = array(
                            'title' => $total->getTitle(),
                            'code' => $total->getCode(),
                            'value' => $total->getAddress()->getShippingInclTax()
                        );
                    } else {
                        $rows[] = array(
                            'title' => $total->getTitle(),
                            'code' => $total->getCode(),
                            'value' => $total->getAddress()->getShippingAmount()
                        );
                    }
                    break;
                case 'subtotal':
                    if ($taxConfig->displayCartSubtotalBoth()) {
                        $rows[] = array(
                            'title' => $taxHelper->__('Subtotal (Excl. Tax)'),
                            'code' => $total->getCode(),
                            'value' => $total->getValueExclTax()
                        );
                        $rows[] = array(
                            'title' => $taxHelper->__('Subtotal (Incl. Tax)'),
                            'code' => $total->getCode() . '_incl',
                            'value' => $total->getValueInclTax()
                        );
                    } else {
                        $rows[] = array(
                            'title' => $total->getTitle(),
                            'code' => $total->getCode(),
                            'value' => $total->getValue()
                        );
                    }
                    break;
                case 'tax':
                    if ($taxConfig->displayFullSummary() && $total->getValue()) {
                        foreach ($total->getFullInfo() as $info) {
                            if (isset($info['hidden']) && $info['hidden']) continue;
                            $rates = isset($info['rates']) ? $info['rates'] : array();
                            foreach ($rates as $rate) {
                                $rows[] = array(
                                    'title' => $taxHelper->escapeHtml(isset($rate['title']) ? $rate['title'] : '') .
                                        (!empty($rate['percent']) ? ' (' . $rate['percent'] . '%)' : ''),
                                    'code' => $total->getCode(),
                                    'value' => isset($info['amount']) ? $info['amount'] : ''
                                );
                            }
                        }
                    }
                    $rows[] = array(
                        'title' => $total->getTitle(),
                        'code' => $total->getCode(),
                        'value' => $total->getValue()
                    );
                    break;
                case 'grand_total':
                    if ($total->getAddress()->getGrandTotal() && $taxConfig->displayCartTaxWithGrandTotal()) {
                        $rows[] = array(
                            'title' => Mage::helper('tax')->__('Grand Total Excl. Tax'),
                            'code' => $total->getCode(),
                            'value' => max($total->getAddress()->getGrandTotal() - $total->getAddress()->getTaxAmount(), 0)
                        );
                        $rows[] = array(
                            'title' => Mage::helper('tax')->__('Grand Total Incl. Tax'),
                            'code' => $total->getCode() . '_incl',
                            'value' => $total->getValue()
                        );
                    } else {
                        $rows[] = array(
                            'title' => $total->getTitle(),
                            'code' => $total->getCode(),
                            'value' => $total->getValue()
                        );
                    }
                    break;
                case 'cashondelivery':
                    if ($taxHelper->isModuleEnabled('Phoenix_CashOnDelivery')) {
                        if (Mage::getStoreConfig('tax/display/cod_fee') == Mage_Tax_Model_Config::DISPLAY_TYPE_BOTH ||
                            Mage::getStoreConfig('tax/display/phoenix_cashondelivery_fee') == Mage_Tax_Model_Config::DISPLAY_TYPE_BOTH
                        ) {
                            $rows[] = array(
                                'title' => $total->getTitle(),
                                'code' => $total->getCode(),
                                'value' => $total->getAddress()->getCodFee()
                            );
                            $rows[] = array(
                                'title' => $total->getTitle(),
                                'code' => $total->getCode() . '_incl',
                                'value' => $total->getAddress()->getCodFee() + $total->getAddress()->getCodTaxAmount()
                            );
                        } elseif (Mage::getStoreConfig('tax/display/cod_fee') == Mage_Tax_Model_Config::DISPLAY_TYPE_INCLUDING_TAX ||
                            Mage::getStoreConfig('tax/display/phoenix_cashondelivery_fee') == Mage_Tax_Model_Config::DISPLAY_TYPE_INCLUDING_TAX
                        ) {
                            $rows[] = array(
                                'title' => $total->getTitle(),
                                'code' => $total->getCode() . '_incl',
                                'value' => $total->getAddress()->getCodFee() + $total->getAddress()->getCodTaxAmount()
                            );
                        } else {
                            $rows[] = array(
                                'title' => $total->getTitle(),
                                'code' => $total->getCode(),
                                'value' => $total->getAddress()->getCodFee()
                            );
                        }
                    } else {
                        $rows[] = array(
                            'title' => $total->getTitle(),
                            'code' => $total->getCode(),
                            'value' => $total->getValue()
                        );
                    }
                    break;
                case 'rewardpoints_label':
                    if (!$taxHelper->isModuleEnabled('Magestore_RewardPoints')) {
                        continue;
                    } else {
                        $rows[] = array(
                            'title' => $total->getTitle(),
                            'code' => $total->getCode(),
                            'value' => $total->getValue()
                        );
                    }
                    break;
                default:
                    $rows[] = array(
                        'title' => $total->getTitle(),
                        'code' => $total->getCode(),
                        'value' => $total->getValue()
                    );
            }
        }

        return $rows;
    }

    public function isNeedCatalogProductLoadAfterEvent()
    {
        return true;
    }

    public function isNeedByPassSessionValidation()
    {
        return Mage::getStoreConfigFlag('web/session/use_remote_addr')
            || Mage::getStoreConfigFlag('web/session/use_http_via')
            || Mage::getStoreConfigFlag('web/session/use_http_x_forwarded_for')
            || Mage::getStoreConfigFlag('web/session/use_http_user_agent')
            || version_compare(Mage::getVersion(), '1.9.3.0', '>=');
    }

    public function isNeedByPassMIMT()
    {
        return version_compare(Mage::getVersion(), '1.9.1.0', '>=')
            && Mage::app()->getFrontController()->getRequest()->isSecure();
    }

    public function isUseSidFrontend()
    {
        return Mage::getStoreConfigFlag('web/session/use_frontend_sid');
    }

    public function addJapiKey($url)
    {
        if (!$url) return '';
        if (!$this->isNeedByPassMIMT() && !$this->isNeedByPassSessionValidation() && $this->isUseSidFrontend()) {
            return $url;
        }

        $sessionId = Mage::getSingleton('core/session')->getSessionId();
        $apiKey = Mage::getStoreConfig('japi/jmango_rest_api/apikey');
        if (!$apiKey) return $url;

        $key = md5($sessionId . $apiKey);
        if (strpos($url, '?') !== false) {
            return $url .= '&jkey=' . $key;
        } else {
            return $url .= '?jkey=' . $key;
        }
    }

    public function getJapiKey()
    {
        $sessionId = Mage::getSingleton('core/session')->getSessionId();
        $apiKey = Mage::getStoreConfig('japi/jmango_rest_api/apikey');
        if (!$apiKey) return null;

        return md5($sessionId . $apiKey);
    }

    public function checkJapiKey()
    {
        if (!$this->isNeedByPassMIMT() && !$this->isNeedByPassSessionValidation()) {
            return true;
        }

        $key = Mage::app()->getRequest()->getParam('jkey');
        if (!$key) return false;

        $sessionId = Mage::app()->getRequest()->getParam('SID');
        $apiKey = Mage::getStoreConfig('japi/jmango_rest_api/apikey');
        if (!$apiKey) return false;

        return md5($sessionId . $apiKey) === $key;
    }

    public function getCountryById($countryId)
    {
        $countries = Mage::getResourceModel('directory/country_collection')->loadData()->toOptionArray();
        foreach ($countries as $country) {
            if ($country['value'] == $countryId) {
                return $country['label'];
            }
        }

        return '';
    }

    public function getRegionById($countryId, $regionId)
    {
        $regions = Mage::getResourceModel('directory/region_collection')->addCountryFilter($countryId)->toOptionArray();

        foreach ($regions as $region) {
            if ($region['value'] == $regionId) {
                return $region['label'];
            }
        }

        return '';
    }

    public function getRequest()
    {
        return $this->getServer()->getRequest();
    }

    public function getResponse()
    {
        return $this->getServer()->getResponse();
    }

    /**
     * @return Jmango360_Japi_Model_Server
     */
    public function getServer()
    {
        return Mage::getSingleton('japi/server');
    }

    public function getUseShopDesignSettingsPath()
    {
        return Mage::getStoreConfigFlag(self::USESHOPDESIGNSETTINGSPATH);
    }

    public function getDefaultRestTheme()
    {
        $theme = Mage::getStoreConfig(self::DEFAULTRESTTHEMEPATH);
        if (empty($theme)) {
            $theme = self::DEFAULTRESTTHEME;
        }

        return $theme;
    }

    public function getDefaultRestPackage()
    {
        $package = Mage::getStoreConfig(self::DEFAULTRESTPACKAGEPATH);
        if (empty($package)) {
            $package = self::DEFAULTRESTPACKAGE;
        }

        return $package;
    }

    /*
     * The "base" package is the Magento base code. This is never updated by custom plugins.
     * -- To be sure never use plugin code you could use "base" as a package name in the REST requests.
     * The "default" package is where plugins standard add there code.
     * -- custom and other plugin code can be added in any other package
     * The theme(name) is set as name for: 'layout', 'template', 'skin', 'locale'
     * -- so if you chose another theme name it could influence language as well
     * Maybe an idea to add a locale setting special for laguage too or maybe create another method to do so
     * -- however, there could already be a better method to do so
     *
    */
    public function setTheme($themeName = null, $packageName = null)
    {
        if (empty($themeName)) {
            $themeName = $this->getDefaultRestTheme();
        }

        if (empty($packageName)) {
            $packageName = $this->getDefaultRestPackage();
        }

        Mage::getDesign()->setArea('frontend')
            ->setPackageName($packageName)
            ->setTheme($themeName);

        return $this;
    }

    /**
     * @param null $layoutName
     * @return Mage_Core_Model_Layout|null
     * @throws Mage_Core_Exception
     */
    public function loadLayout($layoutName = null)
    {
        if (!$layoutName) return null;

        /* @var $layout Mage_Core_Model_Layout */
        $layout = Mage::app()->getLayout();
        $update = $layout->getUpdate();
        $update->load($layoutName);
        $layout->generateXml();
        $layout->generateBlocks();

        return $layout;
    }

    public function getBlock($blockname, $alias = null, $attributes = array())
    {
        foreach (Mage::app()->getLayout()->getAllBlocks() as $name => $block) {
            if ($block->getType() == $blockname) {
                if (!is_null($alias)) {
                    if ($block->getBlockAlias() == $alias) {
                        return $block;
                    }
                } else {
                    return $block;
                }
            }
        }

        return Mage::app()->getLayout()->createBlock($blockname, $alias, $attributes);
    }

    public function stripTokenFromUrl($url)
    {
        if (!is_String($url) || !stristr($url, 'token') || !stristr($url, '?')) {
            return $url;
        }
        $split = (array)explode('?', $url);
        $parts = (array)explode('&', $split[1]);
        foreach ($parts as $key => $part) {
            if (stristr($part, 'token=')) {
                unset($parts[$key]);
            }
        }
        $split[1] = implode('&', $parts);
        $url = implode('?', $split);

        return $url;
    }

    public function getCheckoutUrl()
    {
        /**
         * MPLUGIN-1126: by pass check user's IP adress to auto redirect when website installed "Experius_Geoipredirect"
         */
        if ($this->isModuleEnabled('Experius_Geoipredirect')) {
            Mage::getSingleton('core/session')->setData('ipcheck_redirected', Mage::app()->getStore()->getId());
        }

        if (Mage::getStoreConfigFlag('japi/jmango_rest_checkout_settings/onepage')) {
            $checkoutUrl = Mage::getUrl('japi/checkout/onepage', array('_secure' => true));
            if (Mage::helper('core')->isModuleEnabled('Vaimo_Klarna')) {
                if (Mage::getStoreConfigFlag('payment/vaimo_klarna_checkout/active')) {
                    $checkoutUrl = Mage::getUrl('japi/klarna/checkout', array('_secure' => true));
                }
            }
        } else {
            $checkoutUrl = Mage::getStoreConfig('japi/jmango_rest_checkout_settings/checkout_url');
        }

        if ($checkoutUrl) {
            if (strpos($checkoutUrl, 'http') === 0) {
                return $checkoutUrl;
            } else {
                return Mage::getUrl($checkoutUrl);
            }
        }

        $_cacheUrlKey = 'japi_checkout_url' . Mage::app()->getStore()->getStoreId();
        $cache = Mage::app()->getCache();
        $checkoutUrl = $cache->load($_cacheUrlKey);
        if ($checkoutUrl) {
            return $checkoutUrl;
        }

        /* @var $layout Mage_Core_Model_Layout */
        $layout = Mage::getSingleton('core/layout');
        $update = $layout->getUpdate();
        $update->load('checkout_cart_index');
        $layout->generateXml();
        $layout->generateBlocks();
        foreach ($layout->getAllBlocks() as $name => $block) {
            if ($name == 'checkout.cart.methods') {
                $html = $block->toHtml();
                $value = $this->parseCheckoutUrl($html);
                $cache->save($value, $_cacheUrlKey, array(Mage_Core_Model_Config::CACHE_TAG), null);
                return $value;
                break;
            }
        }

        return '';
    }

    public function parseCheckoutUrl($html)
    {
        if (!$html) return '';

        $doc = new DOMDocument();
        $doc->loadHTML($html);
        $xpath = new DOMXPath($doc);
        $checkoutUrl = '';

        $elements = $xpath->query('//button[contains(@class,"btn-checkout")]');
        foreach ($elements as $element) {
            foreach ($element->attributes as $attribute) {
                if ($attribute->name == 'onclick') {
                    $checkoutUrl = $this->_parseCheckoutUrl($attribute->value);
                }
            }
        }

        if (0) {
            $params = array(
                'SID' => Mage::getSingleton('core/session')->getSessionId(),
                'jkey' => $this->getJapiKey()
            );
            if (strpos($checkoutUrl, '?') !== false) {
                $checkoutUrl .= '&' . http_build_query($params);
            } else {
                $checkoutUrl .= '?' . http_build_query($params);
            }
        }

        return $checkoutUrl;
    }

    protected function _parseCheckoutUrl($text)
    {
        if (!$text) return '';

        if (strpos($text, 'http') === 0) {
            return $text;
        }
        if (strpos($text, 'window') === 0) {
            $chars = array('\'', ';', 'window.location=');
            $replace = array('', '', '');
            return str_replace($chars, $replace, $text);
        }

        return '';
    }

    public function parseTotalsHtml($html)
    {
        if (!$html) return array();

        $doc = new DOMDocument();
        $doc->loadHTML($html);
        $xpath = new DOMXPath($doc);

        $output = array();
        $rows = $xpath->query('//tr');
        foreach ($rows as $row) {
            $columns = $xpath->query('descendant::td', $row);
            $row = array();
            foreach ($columns as $index => $column) {
                if ($index == 0) {
                    $row['title'] = trim($column->nodeValue);
                } elseif ($index == 1) {
                    $row['value'] = trim($column->nodeValue);
                }
            }
            $output[] = $row;
        }

        return $output;
    }

    public function parseHtmlForm($html)
    {
        if (!$html) return array();

        $doc = new DOMDocument();
        $doc->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
        $position = 1;

        $xpath = new DOMXPath($doc);
        $result = $xpath->query('//select');
        $selects = array();
        foreach ($result as $element) {
            $current = array('element' => $element);
            foreach ($element->attributes as $attribute) {
                $current[$attribute->name] = $attribute->value;
            }
            if (!empty($current['id']) && $current['id']) {
                $id = $current['id'];
                $labels = $xpath->query("//label[@for='{$id}']");
                if ($labels->length) {
                    foreach ($labels as $label) {
                        $current['label'] = (string)$label->nodeValue;
                    }
                } else {
                    $labels = $xpath->query('ancestor::label', $element);
                    foreach ($labels as $label) {
                        $current['label'] = $this->stripHtml($label);
                    }
                }
                $position = stripos($html, "id=\"{$id}\"");
                if ($position === false) {
                    $position = stripos($html, "id='{$id}'");
                }
            } else {
                $labels = $xpath->query('ancestor::label', $element);
                foreach ($labels as $label) {
                    $current['label'] = $this->stripHtml($label);
                }
            }
            ++$position;
            $current['position'] = $position;
            $selects[] = $current;
        }
        foreach ($selects as $key => $attributes) {
            $result = $xpath->query("descendant::option", $attributes['element']);
            $hasZero = false;
            foreach ($result as $optionIndex => $element) {
                $currentValue = null;
                foreach ($element->attributes as $attribute) {
                    $attributeName = (string)$attribute->name;
                    if ('value' == $attributeName) {
                        $currentValue = (string)$attribute->value;
                        $currentLabel = trim((string)$element->nodeValue);
                        if ($optionIndex == 0 && $currentValue == '0') {
                            $hasZero = true;
                        }
                        $selects[$key]['options'][$currentValue] = $currentLabel;
                    } elseif ('selected' == $attributeName) {
                        $selects[$key]['selected'] = $currentValue;
                    }
                }
            }
            if ($hasZero) {
                $selects[$key]['options'] = array_reverse($selects[$key]['options'], true);
            }
        }
        $selectOptions = $selects;

        $result = $xpath->query('//input');
        $inputs = array();
        foreach ($result as $element) {
            $current = array('element' => $element);
            foreach ($element->attributes as $attribute) {
                $current[$attribute->name] = $attribute->value;
            }
            if (empty($current['type'])) {
                $current['type'] = 'text';
            }
            if (!empty($current['id']) && $current['id']) {
                $id = $current['id'];
                $labels = $xpath->query("//label[@for='{$id}']");
                if ($labels->length) {
                    foreach ($labels as $label) {
                        $current['label'] = trim((string)$label->nodeValue);
                    }
                    if (!$current['label']) {
                        foreach ($labels as $label) {
                            $images = $xpath->query("descendant::img", $label);
                            foreach ($images as $image) {
                                foreach ($image->attributes as $attribute) {
                                    if ($attribute->name == 'title') {
                                        $current['label'] = $attribute->value;
                                    } elseif ($attribute->name == 'alt') {
                                        $current['label'] = $attribute->value;
                                    }
                                }
                            }
                        }
                    }
                } else {
                    $labels = $xpath->query('ancestor::label', $element);
                    foreach ($labels as $label) {
                        $current['label'] = $this->stripHtml($label);
                    }
                }
                $position = stripos($html, "id=\"{$id}\"");
                if ($position === false) {
                    $position = stripos($html, "id='{$id}'");
                }
            } else {
                $labels = $xpath->query('ancestor::label', $element);
                foreach ($labels as $label) {
                    $current['label'] = $this->stripHtml($label);
                }
            }
            ++$position;
            $current['position'] = $position;
            $inputs[] = $current;
        }

        $inputOptions = $inputs;

        $form = array();
        foreach ($inputOptions as $input) {
            $position = $input['position'];
            unset($input['position']);
            $input['element'] = 'input';
            $form[$position] = $input;
        }
        foreach ($selectOptions as $select) {
            $position = $select['position'];
            unset($select['position']);
            $select['element'] = 'select';
            $form[$position] = $select;
        }

        ksort($form);
        $form = array_values($form);

        return $form;
    }

    protected function stripHtml(DOMNode $node)
    {
        $html = $this->DOMInnerHTML($node);
        $html = preg_replace('#<(select)(?:[^>]+)?>.*?</\1>#s', '', $html);

        return trim(strip_tags(str_replace('&nbsp;', ' ', $html)), " \t\n\r\0\x0B-");
    }

    protected function DOMInnerHTML(DOMNode $element)
    {
        $innerHTML = "";
        $children = $element->childNodes;

        foreach ($children as $child) {
            $innerHTML .= $element->ownerDocument->saveHTML($child);
        }

        return $innerHTML;
    }

    /**
     * Check Plugin update available
     * @return mixed
     */
    public function getUpdateAvailable()
    {
        $_installedVersion = $this->getPluginVersion();

        try {
            $_connectVersion = $this->getPluginVersionFromConnectHtml();
        } catch (Exception $e) {
            return false;
        }

        if (!$_connectVersion) {
            return false;
        }

        $_installedVersion2 = $this->_getMajorVersion($_installedVersion);
        $_connectVersion2 = $this->_getMajorVersion($_connectVersion);

        $data['needUpdate'] = version_compare($_installedVersion2, $_connectVersion2, '<');
        $data['currentVer'] = $_installedVersion;
        $data['connectVer'] = $_connectVersion;

        return $data;
    }

    /**
     * Only notice user if new major version released
     *
     * @param string $version
     * @return string
     */
    protected function _getMajorVersion($version)
    {
        if (!$version) return '';

        $oldParts = explode('.', $version);
        $newParts = array();
        $level = 2;
        for ($i = 0; $i < $level; $i++) {
            if ($i >= $level) break;
            $newParts[] = isset($oldParts[$i]) ? $oldParts[$i] : 0;
        }

        return implode('.', $newParts);
    }

    /**
     * Get current Plugin version installed
     * @return string
     */
    public function getPluginVersion()
    {
        $class = get_class($this);
        $parts = explode('_', $class);
        $module = ucfirst($parts[0]) . '_' . ucfirst($parts[1]);

        $_version = (string)Mage::getConfig()->getNode('modules')->$module->version;

        return $_version;
    }

    /**
     * Get Lastest Plugin version in Magento Connect page
     * @return bool|string
     */
    public function getPluginVersionFromConnectHtml()
    {
        // suppress error reporting
        $currentErrorReportLevel = error_reporting(0);

        $ctx = stream_context_create(array('http' => array('timeout' => 3)));
        $url = 'https://www.magentocommerce.com/magento-connect/jmango360-rest-plugin.html';
        $data = file_get_contents($url, false, $ctx);

        $dom = new DOMDocument();
        $dom->loadHTML($data);

        $xpath = new DOMXPath($dom);
        $nodesUl = $xpath->query('//ul[contains(@class,"extension-version-meta")]');
        if ($nodesUl->length <= 0) {
            return false;
        }

        $nodesItem = $xpath->query('//li[contains(@class,"item")]', $nodesUl->item(0));
        if ($nodesItem->length <= 0) {
            return false;
        }

        $_pluginInfo = $nodesItem->item(0)->nodeValue;
        if ($_pluginInfo == '') {
            return false;
        }

        $_pluginInfoArr = explode(':', $_pluginInfo);
        if (count($_pluginInfoArr) < 2) {
            return false;
        }

        $_pluginVersion = trim($_pluginInfoArr[1]);

        // rollback error reporting
        error_reporting($currentErrorReportLevel);

        return $_pluginVersion;
    }

    public function getLastCheckUpdate()
    {
        return Mage::app()->loadCache('admin_japi_lastcheck_update');
    }

    public function setLastCheckUpdate()
    {
        Mage::app()->saveCache(time(), 'admin_japi_lastcheck_update', array(Mage_Core_Model_Config::CACHE_TAG));
    }

    /**
     * Get Jmango360 system config
     *
     * @return array
     */
    public function getPluginSystemConfigs()
    {
        $data = array();
        /** @var Mage_Core_Model_Config_System $configSystem */
        $configSystem = Mage::getModel('core/config_system');
        $systemXml = $configSystem->load('Jmango360_Japi');

        /** @var Mage_Core_Model_Config_Element $nodes */
        $nodes = $systemXml->getNode('sections/japi/groups')->children();

        /** @var  Mage_Core_Model_Config_Element $value */
        foreach ($nodes as $key => $value) {
            if (in_array($key, $this->systemConfigPathExcludes))
                continue;
            $valueArr = $value->asArray();

            if (count($valueArr)) {
                //Remove some elements not using
                unset($valueArr['@']);
                unset($valueArr['show_in_default']);
                unset($valueArr['show_in_website']);
                unset($valueArr['show_in_store']);
            }
            if ($valueArr['fields']) {
                unset($valueArr['fields']['@']);
                foreach ($valueArr['fields'] as $k => $val) {
                    //Remove some elements not using
                    unset($valueArr['fields'][$k]['@']);
                    unset($valueArr['fields'][$k]['show_in_default']);
                    unset($valueArr['fields'][$k]['show_in_website']);
                    unset($valueArr['fields'][$k]['show_in_store']);
                    unset($valueArr['fields'][$k]['frontend_model']);
                    unset($valueArr['fields'][$k]['backend_model']);
                    unset($valueArr['fields'][$k]['source_model']);

                    //Get config's options
                    $valueArr['fields'][$k]['options'] = $this->_getPluginConfigOptions($val['source_model']);
                    //Get config's value
                    $valueArr['fields'][$k]['value'] = $this->_getPluginConfigValue($key, $k);
                }
            }
            $data['settings'][$key] = $valueArr;
        }
        return $data;
    }

    /**
     * Get Jmango360 config's value
     *
     * @param $group
     * @param $filed
     * @return mixed|string
     */
    protected function _getPluginConfigValue($group, $filed)
    {
        if ($group == 'jmango_rest_api' && $filed == 'version') {
            return $this->getPluginVersion();
        } else {
            $configPath = 'japi/' . $group . '/' . $filed;
            return Mage::getStoreConfig($configPath);
        }
    }

    /**
     * Get Jmango360 config's options
     *
     * @param $sourceModel
     * @return null|array
     */
    protected function _getPluginConfigOptions($sourceModel)
    {
        if (!$sourceModel) return null;
        $model = Mage::getModel($sourceModel);
        if (!is_object($model)) return null;
        if (!$model->toOptionArray()) return null;
        return $model->toOptionArray();
    }

    /**
     * Check Bazaarvoice Conversations enabled
     *
     * @return bool
     */
    public function isBazaarvoiceEnabled()
    {
        return Mage::getStoreConfigFlag('japi/jmango_rest_bazaarvoice_settings/enable');
    }
}