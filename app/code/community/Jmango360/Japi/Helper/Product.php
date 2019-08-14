<?php

class Jmango360_Japi_Helper_Product extends Mage_Core_Helper_Abstract
{
    const CONFIGURABLE_SCP_TYPE = 'configurable_scp';

    protected $_defaultImagesPaths = array(
        'image' => array(
            'width' => 'japi/jmango_rest_gallery_settings/image_width',
            'height' => 'japi/jmango_rest_gallery_settings/image_height',
        ),
        'small_image' => array(
            'width' => 'japi/jmango_rest_gallery_settings/small_image_width',
            'height' => 'japi/jmango_rest_gallery_settings/small_image_height',
        ),
        'thumbnail' => array(
            'width' => 'japi/jmango_rest_gallery_settings/thumbnail_width',
            'height' => 'japi/jmango_rest_gallery_settings/thumbnail_height',
        )
    );

    /**
     * Default ignored attribute codes
     *
     * @var array
     */
    protected $_ignoredAttributeCodes = array(
        'entity_id',
        'attribute_set_id',
        'entity_type_id',
        'tier_price',
        'minimal_price',
        'additional_information'
    );

    /**
     * Default ignored attribute types
     *
     * @var array
     */
    protected $_ignoredAttributeTypes = array(
        'gallery',
        'media_image'
    );

    /**
     * Selectable product attributes
     *
     * @var array
     */
    protected $_selectedAttributes = array(
        'sku',
        'name',
        'description',
        'short_description',
        'visibility',
        'price',
        'special_price',
        'special_from_date',
        'special_to_date',
        'image',
        'media_gallery',
        'hide_in_jm360'
    );

    /**
     * Compacted product attributes
     *
     * @var array
     */
    protected $_compactedAttributes = array(
        'sku',
        'name',
        'description',
        'short_description',
        'visibility',
        'price',
        'image',
        'hide_in_jm360'
    );

    /**
     * Product Attributes used in product view
     *
     * @var array
     */
    protected $_usedInProductView;

    protected $_directionAvailable = array('asc', 'desc');

    /**
     * Check if site use modules like OrganicInternet_SimpleConfigurableProducts
     */
    public function isSCPActive()
    {
        return $this->isModuleEnabled('OrganicInternet_SimpleConfigurableProducts')
        || ($this->isModuleEnabled('Amasty_Conf') && Mage::getStoreConfigFlag('amconf/general/use_simple_price'))
        || ($this->isModuleEnabled('Ayasoftware_SimpleProductPricing') && Mage::getStoreConfigFlag('spp/setting/enableModule'))
        || $this->isModuleEnabled('Itonomy_SimpleConfigurable');
    }

    /**
     * @param $collection Mage_Catalog_Model_Resource_Product_Collection
     * @return $this
     */
    public function addPageSettings($collection)
    {
        /* @var $request Jmango360_Japi_Model_Request */
        $request = Mage::helper('japi')->getRequest();

        $pageSize = $request->getParam('limit', false);
        if ($pageSize) {
            $collection->setPageSize($pageSize);
        }

        $pageNumber = $request->getParam('p', false);
        if ($pageNumber) {
            $collection->setCurPage($pageNumber);
        }

        $field = $request->getParam('order', false);
        if ($field) {
            /**
             * MPLUGIN-767
             */
            $fromPart = $collection->getSelect()->getPart(Zend_Db_Select::FROM);
            if (!isset($fromPart['price_index'])) {
                $this->_addPriceData($collection);
            }
        }

        /**
         * Fix for MPLUGIN-661
         * Remove OREDER BY 'on_top' added by module 'RicardoMartins_OutofstockLast'
         * Update for MPLUGIN-1407: always remove OREDER BY 'on_top'
         */
        if ($this->isModuleEnabled('RicardoMartins_OutofstockLast')) {
            $orderPaths = $collection->getSelect()->getPart(Zend_Db_Select::ORDER);
            foreach ($orderPaths as $key => $orderPath) {
                if ($orderPath[0] == 'on_top') {
                    unset($orderPaths[$key]);
                    break;
                }
            }
            $collection->getSelect()->reset(Zend_Db_Select::ORDER);
            foreach ($orderPaths as $orderPath) {
                $collection->getSelect()->order($orderPath[0] . ' ' . $orderPath[1]);
            }
        }

        return $this;
    }

    /**
     * @param $collection Mage_Catalog_Model_Resource_Product_Collection
     * @param $isSearch bool
     * @return array
     */
    public function getToolbarInfo($collection, $isSearch = false)
    {
        $data = array();

        /* @var $request Jmango360_Japi_Model_Request */
        $request = Mage::helper('japi')->getRequest();

        /* @var $toolBarBlock Mage_Catalog_Block_Product_List_Toolbar */
        $toolBarBlock = Mage::helper('japi')->getBlock('catalog/product_list_toolbar');

        if ($limit = $request->getParam('limit')) {
            $toolBarBlock->setDefaultListPerPage($limit);
            $toolBarBlock->setDefaultGridPerPage($limit);
            $toolBarBlock->addPagerLimit('list', $limit);
            $toolBarBlock->addPagerLimit('grid', $limit);
        }

        /* @var $category Mage_Catalog_Model_Category */
        $category = Mage::getSingleton('catalog/layer')->getCurrentCategory();
        if ($category && $category->getId()) {
            $availableOrders = $category->getAvailableSortByOptions();
            if (!$availableOrders) {
                $availableOrders = $toolBarBlock->getAvailableOrders();
            }
            if ($availableOrders) {
                if ($isSearch) {
                    unset($availableOrders['position']);
                    $availableOrders = array_merge(array(
                        'relevance' => $this->__('Relevance')
                    ), $availableOrders);
                    $defaultOrder = 'relevance';
                    $toolBarBlock->setDefaultDirection('desc');
                } else {
                    $defaultOrder = $category->getDefaultSortBy();
                }
                $toolBarBlock->setAvailableOrders($availableOrders);
                if (isset($availableOrders[$defaultOrder])) {
                    $toolBarBlock->setDefaultOrder($defaultOrder);
                }
            }
        }

        /**
         * MPLUGIN-1433: Override sort direction by Jmango360 config
         */
        $paramDirection = $request->getParam('dir');
        if (empty($paramDirection) || !in_array(strtolower($paramDirection), $this->_directionAvailable)) {
            $directionConfig = Mage::getStoreConfig('japi/jmango_rest_catalog_settings/default_direction');
            if ($directionConfig && !$isSearch)
                $toolBarBlock->setData('_current_grid_direction', $directionConfig);
        }
        $toolBarBlock->setCollection($collection);

        /**
         * MPLUGIN-565
         * MPLUGIN-1039
         * Set products list order for API's return data to same the Web view when sort many products same data
         */
        $storeId = Mage::app()->getStore()->getId();
        $session = Mage::getSingleton('core/session');
        $_isUseFlatOnWeb = $session->getData('use_flat_product_' . $storeId);

        $field = $request->getParam('order', false);
        $direction = $request->getParam('dir', Varien_Data_Collection::SORT_ORDER_DESC);

        /**
         * Fix list order when sort by position and enable Product Flat data
         * Always add sort by 'entity_id' for website http://www.gopro-mania.nl
         */
        if ($field == 'position' && ($_isUseFlatOnWeb || $this->isModuleEnabled('Samiflabs_Shopby'))) {
            $collection->getSelect()->order('cat_index_position ' . strtoupper($direction));
            $collection->setOrder('entity_id', 'asc');
        }

        if (version_compare(Mage::getVersion(), '1.9.0.0', '>=')) {
            $_ignoreOrder = array('position', 'entity_id');
        } else {
            $_ignoreOrder = array('position', 'entity_id', 'relevance');
        }
        if (!in_array($field, $_ignoreOrder)) {
            if ($request->getParam('category_id')) {
                if ($toolBarBlock->getCurrentOrder() != 'position')
                    $collection->setOrder('position', 'asc');
            }
            if ($this->isModuleEnabled('Samiflabs_Shopby')) {
                //Always add sort by 'entity_id' for website http://www.gopro-mania.nl
                $collection->setOrder('entity_id', 'asc');
            }
        }

        Mage::dispatchEvent('catalog_block_product_list_collection', array(
            'collection' => $collection
        ));
        $collection->load();

        $data['current_page_num'] = $toolBarBlock->getCurrentPage();
        $data['last_page_num'] = $toolBarBlock->getLastPageNum();

        $data['current_limit'] = $toolBarBlock->getLimit();
        $data['available_limit'] = $toolBarBlock->getAvailableLimit();

        $data['current_order'] = $toolBarBlock->getCurrentOrder();
        $currentDirection = $this->_getCurrentDirection($collection);
        if (!$currentDirection) $currentDirection = $toolBarBlock->getCurrentDirection();
        $data['current_direction'] = $currentDirection;
        $data['available_orders'] = $toolBarBlock->getAvailableOrders();

        return $data;
    }

    /**
     * Get Toobar direction from layout frontend
     *
     * @param  Mage_Catalog_Model_Resource_Product_Collection $collection
     * @return string
     * @throws Mage_Core_Exception
     */
    protected function _getCurrentDirection($collection)
    {
        //Get sort direction from request
        /* @var $request Jmango360_Japi_Model_Request */
        $request = Mage::helper('japi')->getRequest();
        $dir = $request->getParam('dir', false);
        if ($dir && in_array(strtolower($dir), $this->_directionAvailable)) {
            return $dir;
        }

        if ($request->getAction() == 'search') {
            return '';
        }

        //Get sort direction from Jmango360 config
        $dir = Mage::getStoreConfig('japi/jmango_rest_catalog_settings/default_direction');
        if ($dir && $dir != '') {
            return $dir;
        }

        //Check needed to load sort direction
        $session = Mage::getSingleton('core/session');
        if ($session->getData('japi_direction_loaded')) {
            return '';
        }

        //Get sort direction from frontend layout config
        $layout = Mage::app()->getLayout();
        $update = $layout->getUpdate();
        $update->load('catalog_category_layered');
        //MPLUGIN-1413: fix for 'Amasty_Shopby' - add head block
        if (Mage::helper('core')->isModuleEnabled('Amasty_Shopby')) {
            $layout->addBlock('page/html_head', 'head');
        }
        $layout->generateXml();
        $layout->generateBlocks();
        $block = $layout->getBlock('product_list_toolbar');
        if ($block) {
            $block->setCollection($collection);
            if ($dir = $block->getCurrentDirection()) {
                $session->setData('japi_direction_loaded', true);
                return $dir;
            }
        }

        return '';
    }

    /**
     * Convert a product to collection and return to api json
     *
     * @param int $product
     * @return null|Mage_Catalog_Model_Resource_Product_Collection
     * @throws Jmango360_Japi_Exception
     */
    public function convertProductIdToApiResponse($product)
    {
        if (!is_numeric($product)) {
            return null;
        }

        $collection = Mage::getResourceModel('catalog/product_collection')
            ->setStoreId(Mage::app()->getStore()->getId())
            ->addAttributeToSelect(Mage::getSingleton('catalog/config')->getProductAttributes())
            ->addMinimalPrice()
            ->addFinalPrice()
            ->addTaxPercents()
            ->addIdFilter($product);

        Mage::getSingleton('catalog/product_status')->addVisibleFilterToCollection($collection);
        //Mage::getSingleton('catalog/product_visibility')->addVisibleInCatalogFilterToCollection($collection);

        $result = $this->convertProductCollectionToApiResponse($collection);
        return count($result) ? array_pop($result) : null;
    }

    /**
     * Convert a product to collection and return to api json
     *
     * @param int $product
     * @return null|array
     * @throws Jmango360_Japi_Exception
     */
    public function convertProductIdToApiResponseV2($product)
    {
        if (!is_numeric($product)) {
            return null;
        }

        /* @var $collection Mage_Catalog_Model_Resource_Product_Collection */
        $collection = Mage::getResourceModel('catalog/product_collection')
            ->setStoreId(Mage::app()->getStore()->getId())
            ->addAttributeToSelect(Mage::getSingleton('catalog/config')->getProductAttributes())
            ->addFieldToFilter('type_id', array('in' => array('simple', 'configurable', 'grouped', 'bundle')))
            ->addMinimalPrice()
            ->addFinalPrice()
            ->addTaxPercents()
            ->addIdFilter($product);

        $this->applyHideOnAppFilter($collection);

        Mage::getSingleton('catalog/product_status')->addVisibleFilterToCollection($collection);
        //Mage::getSingleton('catalog/product_visibility')->addVisibleInCatalogFilterToCollection($collection);

        $result = $this->convertProductCollectionToApiResponseV2($collection, true);
        return count($result) ? array_pop($result) : null;
    }

    /**
     * Apply filter 'hide_in_jm360'
     *
     * @param Mage_Catalog_Model_Resource_Product_Collection $collection
     */
    public function applyHideOnAppFilter($collection)
    {
        if (!$collection) return;
        $collection->addAttributeToFilter(array(
            array('attribute' => 'hide_in_jm360', 'null' => true),
            array('attribute' => 'hide_in_jm360', 'eq' => 0)
        ), null, 'left');
    }

    /**
     * Convert some products to collection and return to api json
     *
     * @param array $products
     * @return null|Mage_Catalog_Model_Resource_Product_Collection
     */
    public function convertProductIdsToApiResponse($products)
    {
        if (!count($products)) {
            return array();
        }

        $collection = Mage::getResourceModel('catalog/product_collection')
            ->setStoreId(Mage::app()->getStore()->getId())
            ->addAttributeToSelect(Mage::getSingleton('catalog/config')->getProductAttributes())
            ->addMinimalPrice()
            ->addFinalPrice()
            ->addTaxPercents()
            ->addIdFilter($products);

        return $this->convertProductCollectionToApiResponse($collection);
    }

    /**
     * Default the product collection only return the flat product table depending on flags the stock
     * -- In the API we need all information about the product in one call so we dont have to call on the API more than ones
     * -- this code was originally added by the Duc and Team in Vietnam; just changed a view bits to make it fit foor the REST API
     *
     * @param Mage_Catalog_Model_Resource_Product_Collection $collection
     * @return array
     */
    public function convertProductCollectionToApiResponse(Mage_Catalog_Model_Resource_Product_Collection $collection)
    {
        $collection->setFlag('require_stock_items', true);
        $collection->applyFrontendPriceLimitations();
        $collection->addAttributeToSelect($this->_selectedAttributes);
        $collection->addAttributeToSelect($this->getAttributesUsedInProductView());
        $collection->addTierPriceData();
        $collection->addOptionsToResult();

        /* @var $helper Jmango360_Japi_Helper_Product_Media */
        $helper = Mage::helper('japi/product_media');
        $helper->addMediaGalleryAttributeToCollection($collection);

        $result = array();
        foreach ($collection as $product) {
            $result[] = $this->convertProductToApiResponse($product);
        }

        return $result;
    }

    /**
     * @param Mage_Catalog_Model_Resource_Product_Collection $collection
     * @param bool $details
     * @return array
     */
    public function convertProductCollectionToApiResponseV2(Mage_Catalog_Model_Resource_Product_Collection $collection, $details = false)
    {
        $collection->applyFrontendPriceLimitations();

        if ($details) {
            $attributeDetails = Mage::getStoreConfig('japi/jmango_rest_catalog_settings/attribute_on_details');
            if ($attributeDetails) {
                $collection->addAttributeToSelect($attributeDetails);
            }

            $collection->addAttributeToSelect($this->_selectedAttributes);
            $collection->addAttributeToSelect($this->getAttributesUsedInProductView());
            $collection->addTierPriceData();
            $collection->addOptionsToResult();
        } else {
            $collection->addAttributeToSelect($this->_compactedAttributes);

            $attributeListing = Mage::getStoreConfig('japi/jmango_rest_catalog_settings/attribute_on_listing');
            if ($attributeListing) {
                $collection->addAttributeToSelect($attributeListing);
            }
        }

        // Append review data
        $this->addProductReview($collection);

        $result = array();
        foreach ($collection as $product) {
            if ($details) {
                $result[] = $this->convertProductToApiResponseV2($product, true);
            } else {
                $result[] = $this->convertProductToApiResponseV2($product, false);
            }
        }

        return $result;
    }

    /**
     * Append product review data
     *
     * @param Mage_Catalog_Model_Resource_Product_Collection $collection
     */
    public function addProductReview($collection)
    {
        /* @var $helper Jmango360_Japi_Helper_Product_Review */
        $helper = Mage::helper('japi/product_review');
        if ($helper->isModuleEnabled('Mage_Review')) {
            /* @var $reviewModel Mage_Review_Model_Review */
            $reviewModel = Mage::getModel('review/review');
            $reviewModel->appendSummary($collection);
        }
    }

    /**
     * @param Mage_Catalog_Model_Resource_Product_Collection $collection
     * @return array
     */
    public function convertSuggestProductCollectionToApiResponse(Mage_Catalog_Model_Resource_Product_Collection $collection)
    {
        $result = array();

        $collection->addAttributeToSelect($this->_compactedAttributes);
        foreach ($collection as $product) {
            $result[] = $this->convertProductToApiResponseV3($product);
        }

        return $result;
    }

    /**
     * Prepare product info for suggest api response
     *
     * @param Mage_Catalog_Model_Product $product
     * @return array
     */
    public function convertProductToApiResponseV3(Mage_Catalog_Model_Product $product)
    {
        $result = array(
            'product_id' => (int)$product->getId(),
            'type' => $product->getTypeId(),
            'name' => $product->getName(),
            'image' => $this->_getProductImage($product)
        );

        return $result;
    }

    /**
     * Get product for image
     *
     * @param $product Mage_Catalog_Model_Product
     * @return string
     */
    protected function _getProductImage(Mage_Catalog_Model_Product $product)
    {
        /* @var $helper Mage_Catalog_Helper_Image */
        $helper = Mage::helper('catalog/image');
        $size = $this->_getImageSizes();
        $imageListing = Mage::getStoreConfig('japi/jmango_rest_gallery_settings/image_default_listing');
        if (!$imageListing) $imageListing = 'small_image';
        $imageWidth = !empty($size[$imageListing]['width']) ? $size[$imageListing]['width'] : 400;
        $imageHeight = !empty($size[$imageListing]['height']) ? $size[$imageListing]['height'] : 400;
        $imageFallback = false;
        $image = '';

        if ($this->isModuleEnabled('Softwareimc_Razuna')) {
            /* @var $razunaImageModel Softwareimc_Razuna_Model_Images */
            $razunaImageModel = Mage::getModel('razuna/images');
            $_razunaMainImages = $razunaImageModel->getImagesBySku($product, 'Main');
            if (count($_razunaMainImages) > 0) {
                $image = $_razunaMainImages[0]['url'];
            } else {
                $_razunaThumbImages = $razunaImageModel->getImagesBySku($product, 'Thumbnail');
                if (count($_razunaThumbImages) > 0) {
                    $image = $_razunaMainImages[0]['thumb_url'];
                } else {
                    $imageFallback = true;
                }
            }
        } else {
            $imageFallback = true;
        }

        if ($imageFallback) {
            $image = (string)$helper->init($product, $imageListing)->resize($imageWidth, $imageHeight);
        }

        return $image;
    }

    /**
     * Prepares Product info for api response
     *
     * @param Mage_Catalog_Model_Product $product
     * @param bool $details
     * @return array
     */
    public function convertProductToApiResponseV2(Mage_Catalog_Model_Product $product, $details = false)
    {
        Mage::dispatchEvent('catalog_product_type_configurable_price', array('product' => $product));
        Mage::dispatchEvent('catalog_product_load_after', array('product' => $product, 'data_object' => $product));

        //MPLUGIN-847
        if ($product->getTypeId() == 'bundle') {
            $product->unsetData('final_price');
        }

        $_basePrice = $this->_getSCPBasePrice($product);

        $result = array(
            'product_id' => (int)$product->getId(),
            'name' => $product->getName(),
            'sku' => $product->getSku(),
            'type' => $product->getTypeId(),
            'product_url' => $product->getData('visibility') != '' && $product->getData('visibility') != 1 ? $product->getUrlInStore() : null,
            'type_id' => $product->getTypeId(),
            'stock' => $this->_getStockLevel($product),
            'is_in_stock' => $product->getStockItem() ? (int)$product->getStockItem()->getIsInStock() : null,
            'is_saleable' => (int)$product->isSalable(),
            'price' => $this->calculatePriceIncludeTax($product, $_basePrice),
            'final_price' => $this->calculatePriceIncludeTax($product, $product->getFinalPrice()),
            'min_price' => $this->calculatePriceIncludeTax($product, $product->getMinPrice()),
            'max_price' => $this->calculatePriceIncludeTax($product, $product->getMaxPrice()),
            'minimal_price' => $this->calculatePriceIncludeTax($product, $product->getMinimalPrice()),
            'image' => $this->_getProductImage($product)
        );

        /* @var $reviewHelper Jmango360_Japi_Helper_Product_Review */
        $reviewHelper = Mage::helper('japi/product_review');
        $result['review_enable'] = $reviewHelper->isReviewEnable();
        $this->_addProductReviewSummary($product, $result);

        if ($details) {
            $product->load($product->getId());
            $this->_addTierPriceInfo($product, $result);
            $this->_addGalleryInfo($product, $result);
            $this->_addCustomOptions($product, $result);
            $this->_addConfigurableAttributes($product, $result, true);
            $this->_addGroupedItems($product, $result, true);
            $this->_addBundleInfo($product, $result);
            $this->_addFileInfo($product, $result);
        } else {
            if ($product->getTypeId() == 'bundle') {
                $result['bundle_attributes'] = Mage::helper('japi/product_bundle')->getBundleAttributes($product);
            }
        }

        /* @var $productHelper Mage_Catalog_Helper_Output */
        $productHelper = Mage::helper('catalog/output');

        $attributes = $product->getTypeInstance(false)->getEditableAttributes($product);
        $attributeListing = Mage::getStoreConfig('japi/jmango_rest_catalog_settings/attribute_on_listing');
        $attributeDetails = Mage::getStoreConfig('japi/jmango_rest_catalog_settings/attribute_on_details');
        $hideNullValue = Mage::getStoreConfigFlag('japi/jmango_rest_catalog_settings/hide_null_value');

        foreach ($attributes as $attribute) {
            /* @var $attribute Mage_Catalog_Model_Resource_Eav_Attribute */
            if (!$this->_isAllowedAttribute($attribute)) continue;

            if (!$attribute->hasData('is_visible_on_front')) {
                $attribute->load($attribute->getId());
            }

            $attributeCode = $attribute->getAttributeCode();

            if (in_array($attributeCode, array('short_description', 'description'))) {
                if ($attribute->getData('is_wysiwyg_enabled') == 1) {
                    $html = $this->_getCustomHtmlStyle();
                    $html .= $productHelper->productAttribute(
                        $product, $product->getData($attributeCode), $attributeCode
                    );
                    $result[$attributeCode] = $this->_cleanHtml($html);
                } else {
                    $result[$attributeCode] = $product->getData($attributeCode);
                }
            }

            $value = $attribute->getFrontend()->getValue($product);

            if ($attribute->getFrontendInput() == 'multiselect') {
                $options = $attribute->getFrontend()->getOption($product->getData($attributeCode));
                if (is_array($options)) {
                    $value = implode("\n", $options);
                }
            }

            if ($attribute->getIsVisibleOnFront() || in_array($attributeCode, array($attributeListing, $attributeDetails))) {
                if (!$product->hasData($attributeCode)) {
                    if ($hideNullValue) continue;
                    $value = Mage::helper('catalog')->__('N/A');
                } elseif ($value == '' || $product->getData($attributeCode) == '') {
                    if ($hideNullValue) continue;
                    $value = Mage::helper('catalog')->__('No');
                } elseif ($attribute->getFrontendInput() == 'price' && is_string($value)) {
                    $value = Mage::app()->getStore()->convertPrice($value, true);
                }
            }

            if ($details) {
                if ($attributeCode == $attributeDetails) {
                    $result['detail_display'] = array(
                        'label' => $attribute->getStoreLabel(),
                        'value' => $value,
                        'code' => $attributeCode
                    );
                }
                if ($attribute->getIsVisibleOnFront()) {
                    if (is_string($value) && strlen($value)) {
                        $result['additional_information'][] = array(
                            'label' => $attribute->getStoreLabel(),
                            'value' => $value,
                            'code' => $attributeCode
                        );
                    }
                }
            } elseif ($attributeCode == $attributeListing) {
                $result['list_display'] = array(
                    'label' => $attribute->getStoreLabel(),
                    'value' => $value,
                    'code' => $attributeCode
                );
            }
        }

        /* @var $customerSession Mage_Customer_Model_Session */
        $customerSession = Mage::getSingleton('customer/session');
        if (Mage::getStoreConfigFlag('japi/jmango_app_login_settings/require_login')) {
            if ($customerSession->isLoggedIn()) {
                $result['show_price_label'] = true;
            } else {
                $result['show_price_label'] = false;
            }
        } else {
            $result['show_price_label'] = true;
        }

        if ($result['type'] != $result['type_id']) $result['type'] = $result['type_id'];
        if ($product->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE && $this->isSCPActive()) {
            $result['type'] = self::CONFIGURABLE_SCP_TYPE;
            $result['type_id'] = self::CONFIGURABLE_SCP_TYPE;
        }

        return $result;
    }

    /**
     * Prepares Product info for api response
     *
     * @param Mage_Catalog_Model_Product $product
     * @return array
     */
    public function convertProductToApiResponse(Mage_Catalog_Model_Product $product)
    {
        Mage::dispatchEvent('catalog_product_type_configurable_price', array('product' => $product));
        Mage::dispatchEvent('catalog_product_load_after', array('product' => $product, 'data_object' => $product));

        //MPLUGIN-847
        if ($product->getTypeId() == 'bundle') {
            $product->unsetData('final_price');
        }

        $result = array(
            'product_id' => $product->getId(),
            'sku' => $product->getSku(),
            'set' => $product->getAttributeSetId(),
            'type' => $product->getTypeId(),
            'type_id' => $product->getTypeId(),
            'categories' => $product->getCategoryIds(),
            'websites' => $product->getWebsiteIds(),
            'position' => $product->getCatIndexPosition(),
            'final_price' => $this->calculatePriceIncludeTax($product, $product->getFinalPrice()),
            'stock' => $this->_getStockLevel($product),
            'is_in_stock' => $product->getStockItem() ? (int)$product->getStockItem()->getIsInStock() : null,
            'is_saleable' => (int)$product->isSalable(),
            'min_price' => $this->calculatePriceIncludeTax($product, $product->getMinPrice()),
            'max_price' => $this->calculatePriceIncludeTax($product, $product->getMaxPrice()),
            'minimal_price' => $this->calculatePriceIncludeTax($product, $product->getMinimalPrice()),
            'additional_information' => array(),
            'catalog_display' => array()
        );

        /*DEPRICATED*/
        $this->_addMediaInfo($product, $result);
        /*REPLACES addMediaInfo*/
        $this->_addMediaUrls($product, $result);

        $this->_addCustomOptions($product, $result);
        $this->_addConfigurableAttributes($product, $result);
        $this->_addGroupedItems($product, $result);
        $this->_addBundleInfo($product, $result);
        $this->_addDownloadableInfo($product, $result);
        $this->_addTierPriceInfo($product, $result);

        // MPLUGIN-153
        //$basePriceWithTax = $this->calculatePriceIncludeTax($product, $product->getPrice());
        //$product->setPrice($basePriceWithTax);

        /* @var $productHelper Mage_Catalog_Helper_Output */
        $productHelper = Mage::helper('catalog/output');

        $attributes = $product->getTypeInstance(false)->getEditableAttributes($product);
        $attributeListing = Mage::getStoreConfig('japi/jmango_rest_catalog_settings/attribute_on_listing');
        $attributeDetails = Mage::getStoreConfig('japi/jmango_rest_catalog_settings/attribute_on_details');
        $hideNullValue = Mage::getStoreConfigFlag('japi/jmango_rest_catalog_settings/hide_null_value');

        foreach ($attributes as $attribute) {
            /* @var $attribute Mage_Catalog_Model_Resource_Eav_Attribute */
            if (!$this->_isAllowedAttribute($attribute)) continue;

            //MPLUGIN-975
            if (!$attribute->hasData('is_wysiwyg_enabled') || !$attribute->hasData('is_visible_on_front')) {
                $attribute->load($attribute->getId());
            }

            $attributeCode = $attribute->getAttributeCode();

            if ($attribute->getData('is_wysiwyg_enabled') == 1) {
                /**
                 * MPLUGIN-1031: Validate data of attribute
                 * Return empty data if value of attribute null or contains only html tags
                 */
                $attrContent = $productHelper->productAttribute(
                    $product, $product->getData($attributeCode), $attributeCode
                );
                if (!$attrContent || $attrContent == '' || trim(strip_tags($attrContent)) == '') {
                    $result[$attributeCode] = '';
                } else {
                    $html = $this->_getCustomHtmlStyle();
                    $html .= $attrContent;
                    $result[$attributeCode] = $this->_cleanHtml($html);
                }
            } else {
                $result[$attributeCode] = $product->getData($attributeCode);
            }

            $value = '';

            if ($attribute->getIsVisibleOnFront() || $attributeListing == $attributeCode || $attributeDetails == $attributeCode) {
                if ($attribute->getFrontendInput() == 'multiselect') {
                    $options = $attribute->getFrontend()->getOption($product->getData($attributeCode));
                    if (is_array($options)) {
                        $value = implode("\n", $options);
                    }
                } else {
                    $value = $attribute->getFrontend()->getValue($product);
                }
                if (!$product->hasData($attributeCode)) {
                    if ($hideNullValue) continue;
                    $value = Mage::helper('catalog')->__('N/A');
                } elseif ((string)$value == '') {
                    $value = Mage::helper('catalog')->__('No');
                } elseif ($attribute->getFrontendInput() == 'price' && is_string($value)) {
                    $value = Mage::app()->getStore()->convertPrice($value, true);
                }
            }

            if ($attribute->getIsVisibleOnFront()) {
                if (is_string($value) && strlen($value)) {
                    $result['additional_information'][] = array(
                        'label' => $attribute->getStoreLabel(),
                        'value' => $value,
                        'code' => $attributeCode
                    );
                }
            }

            if ($attributeListing == $attributeCode) {
                if (is_string($value) && strlen($value)) {
                    $result['catalog_display']['list'] = array(
                        'label' => $attribute->getStoreLabel(),
                        'value' => $value,
                        'code' => $attributeCode
                    );
                }
            }

            if ($attributeDetails == $attributeCode) {
                if (is_string($value) && strlen($value)) {
                    $result['catalog_display']['details'] = array(
                        'label' => $attribute->getStoreLabel(),
                        'value' => $value,
                        'code' => $attributeCode
                    );
                }
            }
        }

        if (empty($result['catalog_display'])) {
            $result['catalog_display'] = new stdClass();
        }

        if ($result['type'] != $result['type_id']) $result['type'] = $result['type_id'];
        if ($product->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE && $this->isSCPActive()) {
            $result['type'] = self::CONFIGURABLE_SCP_TYPE;
            $result['type_id'] = self::CONFIGURABLE_SCP_TYPE;
        }

        $result['price'] = $this->calculatePriceIncludeTax($product, $this->_getSCPBasePrice($product));

        return $result;
    }

    /**
     * Return download files from MageWorx_Downloads
     *
     * @param Mage_Catalog_Model_Product $product
     * @param $result
     */
    protected function _addFileInfo($product, &$result)
    {
        if (!$this->isModuleEnabled('MageWorx_Downloads')) return;
        $result['attachment'] = Mage::helper('japi/product_file')->getItems($product);
    }

    /**
     * Return custom CSS for WYSIWYG field
     */
    protected function _getCustomHtmlStyle()
    {
        $css = '<style type="text/css">';
        $css .= 'pre,code,blockquote{white-space:normal!important;}';
        $css .= 'table{width:100%!important;}';
        if ($custtomCss = Mage::getStoreConfig('japi/jmango_rest_catalog_settings/custom_css')) {
            $css .= str_replace("\n", '', $custtomCss);
        }
        $css .= '</style>';

        return $css;
    }

    /**
     * Remove: &nbsp;
     *
     * @param string $html
     * @return string
     */
    protected function _cleanHtml($html)
    {
        if (!$html) return $html;
        return str_replace('&nbsp; ', ' ', str_replace('&nbsp;&nbsp;', ' ', $html));
    }

    /**
     * @param $product Mage_Catalog_Model_Product
     * @param $result
     */
    protected function _addMediaUrls($product, &$result)
    {
        $imageNames = array_keys($this->_defaultImagesPaths);
        $size = $this->_getImageSizes();
        /* @var $helper Mage_Catalog_Helper_Image */
        $helper = Mage::helper('catalog/image');
        $images = array();
        /**
         * MPLUGIN-761
         * Support for websites using 'Softwareimc_Razuna' extension
         */
        if ($this->isModuleEnabled('Softwareimc_Razuna')) {
            /* @var $razunaImageModel Softwareimc_Razuna_Model_Images */
            $razunaImageModel = Mage::getModel('razuna/images');
            $_razunaMainImages = $razunaImageModel->getImagesBySku($product, 'Main');
            if (count($_razunaMainImages) > 0) {
                $images['thumbnail']['url'] = $_razunaMainImages[0]['url'];
            } else {
                $_razunaThumbImages = $razunaImageModel->getImagesBySku($product, 'Thumbnail');
                if (count($_razunaThumbImages) > 0) {
                    $images['thumbnail']['url'] = $_razunaMainImages[0]['thumb_url'];
                } else {
                    $_imageListingDefault = Mage::getStoreConfig('japi/jmango_rest_gallery_settings/image_default_listing');
                    $images['thumbnail']['url'] = (string)$helper->init($product, $_imageListingDefault)->resize($size['thumbnail']['width'], $size['thumbnail']['height']);
                }
            }
            $images['thumbnail']['label'] = $product->getName();
        } else {
            $_imageListingDefault = Mage::getStoreConfig('japi/jmango_rest_gallery_settings/image_default_listing');

            foreach ($imageNames as $imageName) {
                if ($imageName == 'thumbnail' && $_imageListingDefault != 'thumbnail') { // Replaces thumbnail url by config
                    $images[$imageName]['url'] = (string)$helper->init($product, $_imageListingDefault)->resize($size[$imageName]['width'], $size[$imageName]['height']);
                } else {
                    $images[$imageName]['url'] = (string)$helper->init($product, $imageName)->resize($size[$imageName]['width'], $size[$imageName]['height']);
                }
                $images[$imageName]['label'] = (string)$product->getData($imageName . '_label') ? $product->getData($imageName . '_label') : $product->getName();
            }
        }

        //MPLUGIN-1133 - get Ignore image - will not add to gallery data
        $_ignoreName = $this->_getImageFileName($images['thumbnail']['url']);

        /**
         * Fix for issues MPLUGIN-727, MPLUGIN-764
         * Load media gallery form 'media_gallery' data instead of load media gallery images object
         * Update for MPLUGIN-761 - Support for websites using 'Softwareimc_Razuna' extension
         */
        if (!$this->isModuleEnabled('Softwareimc_Razuna')) { // not installed 'Softwareimc_Razuna'
            $images['gallery'] = $this->_getGalleryUrlsData($product, $_ignoreName);
        } else {// installed 'Softwareimc_Razuna'
            /* @var $razunaImageModel Softwareimc_Razuna_Model_Images */
            $razunaImageModel = Mage::getModel('razuna/images');
            $_razunaThumbImages = $razunaImageModel->getImagesBySku($product, 'Thumbnail');
            if (count($_razunaThumbImages) > 0) {
                $index = 0;
                foreach ($_razunaThumbImages as $_thumb) {
                    $images['gallery'][$index]['url'] = $_thumb['thumb_url'];
                    $images['gallery'][$index]['label'] = $product->getName();
                    $index++;
                }
            } else {
                $images['gallery'] = $this->_getGalleryUrlsData($product, $_ignoreName);
            }
        }

        $result['media_urls'] = $images;
    }

    protected function _getGalleryUrlsData(Mage_Catalog_Model_Product $product, $ignoreName)
    {
        /* @var $helper Mage_Catalog_Helper_Image */
        $helper = Mage::helper('catalog/image');
        $size = $this->_getImageSizes();
        $images = array();

        $gallery = $product->getData('media_gallery');
        if (version_compare(Mage::getVersion(), '1.9.0', '<')) {
            $gallery = $gallery['images'];
        } else {
            if ($product->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE) {
                $gallery = !empty($gallery['configurable_images']) ? $gallery['configurable_images'] : $gallery['images'];
            } else {
                $gallery = $gallery['images'];
            }
        }

        if (count($gallery)) {
            $index = 0;
            foreach ($gallery as $image) {
                if ($image['disabled_default']) continue;

                //MPLUGIN-1133 - not add image used on listing to gallery
                $_imageFileName = $this->_getImageFileName($image['file']);
                if ($_imageFileName == $ignoreName) continue;

                $images[$index]['url'] = (string)$helper->init($product, 'thumbnail', $image['file'])->resize($size['thumbnail']['width'], $size['thumbnail']['height']);
                $images[$index]['label'] = $image['label'] ? $image['label'] : $product->getName();
                $index++;
            }
        }
        return $images;
    }

    public function getImageSizes()
    {
        return $this->_getImageSizes();
    }

    protected function _getImageSizes()
    {
        /* @var $request Jmango360_Japi_Model_Request */
        $request = Mage::helper('japi')->getRequest();

        $imageNames = array_keys($this->_defaultImagesPaths);
        $imageSizesParams = $request->getParam('image_sizes', array());

        /**
         * MPLUGIN-1134 - Updated: Only get images size from store config, not check data in session
         */
        $sizes = array();
        foreach ($imageNames as $imageName) {
            if (!empty($imageSizesParams[$imageName]['width'])) {
                $sizes[$imageName]['width'] = $imageSizesParams[$imageName]['width'];
                if (empty($imageSizesParams[$imageName]['height'])) {
                    $imageSizesParams[$imageName]['height'] = $imageSizesParams[$imageName]['width'];
                }
            } elseif (Mage::getStoreConfig($this->_defaultImagesPaths[$imageName]['width'])) {
                $sizes[$imageName]['width'] = Mage::getStoreConfig($this->_defaultImagesPaths[$imageName]['width']);
            } else {
                $sizes[$imageName]['width'] = null;
            }

            if (!empty($imageSizesParams[$imageName]['height'])) {
                $sizes[$imageName]['height'] = $imageSizesParams[$imageName]['height'];
            } elseif (Mage::getStoreConfig($this->_defaultImagesPaths[$imageName]['height'])) {
                $sizes[$imageName]['height'] = Mage::getStoreConfig($this->_defaultImagesPaths[$imageName]['height']);
            } else {
                $sizes[$imageName]['height'] = null;
            }
        }

        return $sizes;
    }

    /**
     * This function will return product final price with/without tax
     * that based on Tax settings in Sale -> Tax & System -> Sale -> Tax
     * and convert to store currency
     *
     * @param Mage_Catalog_Model_Product $_product
     * @param float $productFinalPrice
     * @param bool $convertPrice
     * @return float
     */

    public function calculatePriceIncludeTax(Mage_Catalog_Model_Product $_product, $productFinalPrice, $convertPrice = true)
    {
        if (version_compare(Mage::getVersion(), '1.8.1.0', '<')) {
            /* @var $taxHelper Jmango360_Japi_Helper_Tax */
            $taxHelper = Mage::helper('japi/tax');
        } else {
            /* @var $taxHelper Mage_Tax_Helper_Data */
            $taxHelper = Mage::helper('tax');
        }

        /**
         * MPLUGIN-980: get Cutomer tax class when customer logged-in or not
         */
        if (Mage::getSingleton('customer/session')->isLoggedin()) {
            $customerTaxClass = Mage::getSingleton('tax/calculation')->getRateRequest()->getCustomerClassId();
        } else {
            $customerTaxClass = Mage::getModel('customer/group')->getTaxClassId(Mage_Customer_Model_Group::NOT_LOGGED_IN_ID);
        }

        /**
         * Get product price display type
         *  1 - Excluding tax
         *  2 - Including tax
         *  3 - Both
         */

        /* @var Mage_Tax_Model_Config */
        $productPriceDisplayType = $taxHelper->getPriceDisplayType(Mage::app()->getStore()->getId());

        if ($productPriceDisplayType == 1) {
            // Exclude tax
            $productFinalPrice = $taxHelper->getPrice($_product, $productFinalPrice, false, null, null, $customerTaxClass, null, null, false);
        } else {
            // Including tax or both
            $productFinalPrice = $taxHelper->getPrice($_product, $productFinalPrice, true, null, null, $customerTaxClass, null, null, false);
        }

        if ($convertPrice) {
            // Convert store price
            $store = Mage::app()->getStore();
            $productFinalPrice = $store->convertPrice($productFinalPrice, false, false);
        }

        return $productFinalPrice;
    }

    /**
     * Return the stock level if user manage stock otherwise return -1
     * @param Mage_Catalog_Model_Product $_product
     * @return int stock level
     */
    protected function _getStockLevel(Mage_Catalog_Model_Product $_product)
    {
        $manageStock = $_product->getStockItem() ? $_product->getStockItem()->getManageStock() : null;

        $stockQuantity = -1;
        if ($manageStock == 1) {
            $stockQuantity = $_product->getStockItem()->getQty();
        }

        return (float)$stockQuantity;
    }

    /**
     * DEPRICATED
     * @param Mage_Catalog_Model_Product $product
     * @param $result
     */
    protected function _addMediaInfo(Mage_Catalog_Model_Product $product, &$result)
    {
        /* @var $helper Jmango360_Japi_Helper_Product_Media */
        /*DEPRICATED*/
        $helper = Mage::helper('japi/product_media');
        $result['media_info'] = $helper->getMediaInfo($product);
    }

    protected function _addGalleryInfo(Mage_Catalog_Model_Product $product, &$result)
    {
        if ($this->isModuleEnabled('Softwareimc_Razuna')) {
            /* @var $razunaImageModel Softwareimc_Razuna_Model_Images */
            $razunaImageModel = Mage::getModel('razuna/images');
            $_razunaThumbImages = $razunaImageModel->getImagesBySku($product, 'Thumbnail');
            if (count($_razunaThumbImages) > 0) {
                $index = 0;
                foreach ($_razunaThumbImages as $_thumb) {
                    $images['gallery'][$index]['url'] = $_thumb['thumb_url'];
                    $images['gallery'][$index]['label'] = $product->getName();
                    $index++;
                }
                return;
            }
        }

        /* @var $helper Mage_Catalog_Helper_Image */
        $helper = Mage::helper('catalog/image');
        $size = $this->_getImageSizes();
        $images = array();

        $gallery = $product->getMediaGalleryImages();
        if (version_compare(Mage::getVersion(), '1.9.0', '<')) {
            if (is_array($gallery) && isset($gallery['images'])) {
                $gallery = $gallery['images'];
            }
        } else {
            if ($product->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE) {
                if (is_array($gallery) && isset($gallery['configurable_images'])) {
                    $gallery = count($gallery['configurable_images']) ? $gallery['configurable_images'] : $gallery['images'];
                } else {
                    if (is_array($gallery) && isset($gallery['images'])) {
                        $gallery = $gallery['images'];
                    }
                }
            } else {
                if (is_array($gallery) && isset($gallery['images'])) {
                    $gallery = $gallery['images'];
                }
            }
        }
        $_ignoreName = $this->_getImageFileName($result['image']);
        if (count($gallery)) {
            foreach ($gallery as $image) {
                /* @var $image Varien_Object */
                if ($image->getDisabledDefault()) continue;

                //MPLUGIN-1133 - not add image used on listing to gallery
                $_imageName = $this->_getImageFileName($image->getFile());
                if ($_imageName == $_ignoreName) continue;

                $images[] = array(
                    'url' => (string)$helper->init($product, 'thumbnail', $image->getFile())
                        ->resize($size['thumbnail']['width'], $size['thumbnail']['height']),
                    'label' => $image->getLabel() ? $image->getLabel() : $product->getName()
                );
            }
        }

        $result['gallery'] = $images;
    }

    /**
     * Get product review summary
     *
     * @param Mage_Catalog_Model_Product $product
     * @param $result
     */
    protected function _addProductReviewSummary($product, &$result)
    {
        /* @var $helper Jmango360_Japi_Helper_Product_Review */
        $helper = Mage::helper('japi/product_review');
        $reviewSummary = $helper->getProductReviewSummary($product);
        if ($reviewSummary) {
            $result['review'] = array(
                'type' => 'overview',
                'code' => 'overview',
                'values' => array('1', '2', '3', '4', '5'),
                'review_counter' => $helper->getProductReviewCount($product),
                'percent' => $reviewSummary
            );
        } else {
            $result['review'] = null;
        }
    }

    /**
     * Get product review details
     *
     * @param Mage_Catalog_Model_Product $product
     * @param $result
     */
    protected function _addProductReviewDetails($product, &$result)
    {
        /* @var $helper Jmango360_Japi_Helper_Product_Review */
        $helper = Mage::helper('japi/product_review');
        $result['review_details'] = $helper->getProductReviewDetails($product);
    }

    /**
     * @param Mage_Catalog_Model_Product $product
     * @param $result
     */
    protected function _addCustomOptions(Mage_Catalog_Model_Product $product, &$result)
    {
        /* @var $helper Jmango360_Japi_Helper_Product_Options */
        $helper = Mage::helper('japi/product_options');
        $result['options'] = $helper->getOptionList($product);
    }

    /**
     * @param Mage_Catalog_Model_Product $product
     * @param $result
     * @param $includePrice
     */
    protected function _addConfigurableAttributes(Mage_Catalog_Model_Product $product, &$result, $includePrice = false)
    {
        if ($product->getTypeId() != Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE) {
            return;
        }

        /* @var $helper Jmango360_Japi_Helper_Product_Configurable */
        $helper = Mage::helper('japi/product_configurable');
        $result['configurable_attributes'] = $helper->getConfigurableAttributes($product, $includePrice);
    }

    /**
     * @param Mage_Catalog_Model_Product $product
     * @param $includePrice bool
     * @param $result
     */
    protected function _addGroupedItems(Mage_Catalog_Model_Product $product, &$result, $includePrice = false)
    {
        if ($product->getTypeId() != Mage_Catalog_Model_Product_Type::TYPE_GROUPED) {
            return;
        }
        /* @var $helper Jmango360_Japi_Helper_Product_Grouped */
        $helper = Mage::helper('japi/product_grouped');
        $result['grouped_items'] = $helper->getGroupedItems($product, $includePrice);
    }

    /**
     * @param Mage_Catalog_Model_Product $product
     * @param $result
     */
    protected function _addBundleInfo(Mage_Catalog_Model_Product $product, &$result)
    {
        if ($product->getTypeId() != Mage_Catalog_Model_Product_Type::TYPE_BUNDLE) {
            return;
        }
        /* @var $helper Jmango360_Japi_Helper_Product_Bundle */
        $helper = Mage::helper('japi/product_bundle');
        $result['bundle_attributes'] = $helper->getBundleAttributes($product);
        $result['bundle_items'] = $helper->getBundleItems($product);
    }

    /**
     * @param Mage_Catalog_Model_Product $product
     * @param $result
     */
    protected function _addDownloadableInfo(Mage_Catalog_Model_Product $product, &$result)
    {
        if ($product->getTypeId() !== Mage_Downloadable_Model_Product_Type::TYPE_DOWNLOADABLE) {
            return;
        }
        /* @var $helper Jmango360_Japi_Helper_Product_Downloadable */
        $helper = Mage::helper('japi/product_downloadable');
        $result['downloadable_info'] = $helper->getDownloadableLinks($product);
    }

    /**
     * @param Mage_Catalog_Model_Product $product
     * @param $result
     */
    protected function _addTierPriceInfo(Mage_Catalog_Model_Product $product, &$result)
    {
        /* @var $helper Jmango360_Japi_Helper_Product_TierPrice */
        $helper = Mage::helper('japi/product_tierPrice');
        $result['tier_price'] = $helper->getTierPriceInfo($product);
    }

    /**
     * Check is attribute allowed
     *
     * @param Mage_Eav_Model_Entity_Attribute_Abstract $attribute
     * @param array $attributes
     * @return bool
     */
    protected function _isAllowedAttribute($attribute, $attributes = null)
    {
        return !in_array($attribute->getFrontendInput(), $this->_ignoredAttributeTypes)
        && !in_array($attribute->getAttributeCode(), $this->_ignoredAttributeCodes);
    }

    /**
     * Returns max possible amount of products according to memory limit settings
     * Copied from Mage_ImportExport_Model_Export_Entity_Product::export()
     *
     * @return int
     */
    public function getProductLimit()
    {

        $memoryLimit = trim(ini_get('memory_limit'));
        $lastMemoryLimitLetter = strtolower($memoryLimit[strlen($memoryLimit) - 1]);
        switch ($lastMemoryLimitLetter) {
            case 'g':
                $memoryLimit *= 1024;
                break;
            case 'm':
                $memoryLimit *= 1024;
                break;
            case 'k':
                $memoryLimit *= 1024;
                break;
            default:
                // minimum memory required by Magento
                $memoryLimit = 250000000;
        }

        // Tested one product to have up to such size
        $memoryPerProduct = 100000;
        // Decrease memory limit to have supply
        $memoryUsagePercent = 0.8;
        // Minimum Products limit
        $minProductsLimit = 500;

        $productLimit = intval(($memoryLimit * $memoryUsagePercent - memory_get_usage(true)) / $memoryPerProduct);
        if ($productLimit < $minProductsLimit) {
            $productLimit = $minProductsLimit;
        }

        return $productLimit;
    }

    /**
     *
     * Get product
     *
     * @param $productId
     * @param int $storeId
     * @return mixed
     */
    public function getProduct($productId, $storeId = null)
    {
        $product = Mage::getModel('catalog/product');
        if ($storeId !== null) {
            $product->setStoreId($storeId);
        }

        if (is_string($productId)) {
            $idBySku = $product->getIdBySku($productId);
            if ($idBySku) {
                $product->load($idBySku);
            }
        } else {
            $product->load($productId);
        }
        return $product;
    }

    /**
     * Retrieve Attributes used in product listing
     *
     * @return array
     */
    public function getAttributesUsedInProductView()
    {
        if (is_null($this->_usedInProductView)) {
            $this->_usedInProductView = array();
            $attributesData = $this->_getAttributesUsedInProductView();
            foreach ($attributesData as $attributeData) {
                $this->_usedInProductView[] = $attributeData['attribute_code'];
            }
        }

        $attributeListing = Mage::getStoreConfig('japi/jmango_rest_catalog_settings/attribute_on_listing');
        if (!in_array($attributeListing, $this->_usedInProductView)) $this->_usedInProductView[] = $attributeListing;
        $attributeDetails = Mage::getStoreConfig('japi/jmango_rest_catalog_settings/attribute_on_details');
        if (!in_array($attributeDetails, $this->_usedInProductView)) $this->_usedInProductView[] = $attributeDetails;

        return $this->_usedInProductView;
    }

    /**
     * Retrieve Product Attributes Used in Catalog Product listing
     *
     * @return array
     */
    protected function _getAttributesUsedInProductView()
    {
        /* @var $resource Mage_Core_Model_Resource */
        $resource = Mage::getSingleton('core/resource');
        $adapter = $resource->getConnection('core_read');
        $entityTypeId = Mage::getSingleton('eav/config')->getEntityType(Mage_Catalog_Model_Product::ENTITY)->getId();
        $storeId = Mage::app()->getStore()->getId();
        $storeLabelExpr = $adapter->getCheckSql('al.value IS NOT NULL', 'al.value', 'main_table.frontend_label');

        $select = $adapter->select()
            ->from(array('main_table' => $resource->getTableName('eav/attribute')))
            ->join(
                array('additional_table' => $resource->getTableName('catalog/eav_attribute')),
                'main_table.attribute_id = additional_table.attribute_id'
            )
            ->joinLeft(
                array('al' => $resource->getTableName('eav/attribute_label')),
                'al.attribute_id = main_table.attribute_id AND al.store_id = ' . (int)$storeId,
                array('store_label' => $storeLabelExpr)
            )
            ->where('main_table.entity_type_id = ?', (int)$entityTypeId)
            ->where('additional_table.is_visible_on_front = ?', 1);

        return $adapter->fetchAll($select);
    }

    /**
     * Add Price data to products collection if not exist
     *
     * @param $collection
     * @param bool $joinLeft
     * @return $this
     * @throws Mage_Core_Exception
     */
    protected function _addPriceData($collection, $joinLeft = false)
    {
        $resource = Mage::getSingleton('core/resource');
        $customerGroupId = Mage::getSingleton('customer/session')->getCustomerGroupId();
        $websiteId = Mage::app()->getWebsite()->getId();

        $select = $collection->getSelect();
        $connection = $collection->getConnection();

        $joinCond = join(' AND ', array(
            'price_index.entity_id = e.entity_id',
            $connection->quoteInto('price_index.website_id = ?', $websiteId),
            $connection->quoteInto('price_index.customer_group_id = ?', $customerGroupId)
        ));

        $least = $connection->getLeastSql(array('price_index.min_price', 'price_index.tier_price'));
        $minimalExpr = $connection->getCheckSql('price_index.tier_price IS NOT NULL',
            $least, 'price_index.min_price');
        $colls = array('price', 'tax_class_id', 'final_price',
            'minimal_price' => $minimalExpr, 'min_price', 'max_price', 'tier_price');
        $tableName = array('price_index' => $resource->getTableName('catalog/product_index_price'));
        if ($joinLeft) {
            $select->joinLeft($tableName, $joinCond, $colls);
        } else {
            $select->join($tableName, $joinCond, $colls);
        }

        //Clean duplicated fields
        $helper = Mage::getResourceHelper('core');
        $helper->prepareColumnsList($select);

        return $this;
    }

    /**
     * Get buy request data
     *
     * @param $item Mage_Sales_Model_Quote_Item
     * @param $product array
     * @return null|array
     */
    public function getCartProductBuyRequest($item, $product)
    {
        if (!$item || !$product) return null;

        $optionCollection = Mage::getModel('sales/quote_item_option')->getCollection()->addItemFilter($item);
        $item->setOptions($optionCollection->getOptionsByItem($item));
        $buyRequest = $item->getBuyRequest();
        if ($buyRequest) {
            $buyRequestData = array();

            switch ($product['type_id']) {
                case Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE:
                case self::CONFIGURABLE_SCP_TYPE:
                    $buyRequestData['super_attribute'] = $buyRequest->getData('super_attribute');
                    $buyRequestData['qty'] = $buyRequest->getData('qty');
                    break;
                case Mage_Catalog_Model_Product_Type::TYPE_BUNDLE:
                    $options = $buyRequest->getData('bundle_option');
                    if (is_array($options) && !empty($options)) {
                        foreach ($options as $key => $value) {
                            if (empty($value)) {
                                unset($options[$key]);
                            } elseif (!is_array($value)) {
                                $options[$key] = array($value);
                            }
                        }
                        $buyRequestData['bundle_option'] = $options;
                    } else {
                        $buyRequestData['bundle_option'] = null;
                    }
                    $buyRequestData['bundle_option_qty'] = $this->_getBundleOptionQty($item);
                    $buyRequestData['qty'] = $buyRequest->getData('qty');
                    break;
            }

            $options = $buyRequest->getData('options');
            if (is_array($options) && !empty($options)) {
                foreach ($options as $key => $value) {
                    if (empty($value)) {
                        unset($options[$key]);
                    } elseif (!is_array($value)) {
                        $options[$key] = array($value);
                    } elseif (isset($value['hour']) || isset($value['day']) || isset($value['type'])) {
                        unset($options[$key]);
                    }
                }
                $buyRequestData['options'] = empty($options) ? null : $options;
            } else {
                $buyRequestData['options'] = null;
            }

            $buyRequestData['qty'] = $buyRequest->getData('qty');

            return !empty($buyRequestData) ? $buyRequestData : null;
        } else {
            return null;
        }
    }

    /**
     * Get bundle options selections qty
     *
     * @param $item Mage_Sales_Model_Quote_Item
     * @return array
     */
    protected function _getBundleOptionQty($item)
    {
        $options = array();
        $product = $item->getProduct();
        $typeInstance = $product->getTypeInstance(true);
        $optionsQuoteItemOption = $item->getOptionByCode('bundle_option_ids');
        $bundleOptionsIds = $optionsQuoteItemOption ? unserialize($optionsQuoteItemOption->getValue()) : array();
        if ($bundleOptionsIds) {
            $optionsCollection = $typeInstance->getOptionsByIds($bundleOptionsIds, $product);
            $selectionsQuoteItemOption = $item->getOptionByCode('bundle_selection_ids');
            $bundleSelectionIds = unserialize($selectionsQuoteItemOption->getValue());
            if (!empty($bundleSelectionIds)) {
                $selectionsCollection = $typeInstance->getSelectionsByIds(
                    unserialize($selectionsQuoteItemOption->getValue()),
                    $product
                );
                $bundleOptions = $optionsCollection->appendSelections($selectionsCollection, true);
                foreach ($bundleOptions as $bundleOption) {
                    if ($bundleOption->getSelections()) {
                        $bundleSelections = $bundleOption->getSelections();
                        foreach ($bundleSelections as $bundleSelection) {
                            $options[$bundleOption->getOptionId()][] = sprintf("%d:%d",
                                $bundleSelection->getSelectionId(),
                                $this->_getSelectionQty($product, $bundleSelection->getSelectionId()) * 1
                            );
                        }
                    }
                }
            }
        }

        return empty($options) ? null : $options;
    }

    /**
     * Get selection quantity
     *
     * @param Mage_Catalog_Model_Product $product
     * @param int $selectionId
     *
     * @return decimal
     */
    protected function _getSelectionQty($product, $selectionId)
    {
        $selectionQty = $product->getCustomOption('selection_qty_' . $selectionId);
        if ($selectionQty) {
            return $selectionQty->getValue();
        }
        return 0;
    }

    /**
     * Get SCP Base price
     *
     * @param Mage_Catalog_Model_Product $product
     * @return float
     */
    protected function _getSCPBasePrice($product)
    {
        if ($product->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE &&
            $this->isModuleEnabled('OrganicInternet_SimpleConfigurableProducts')
        ) {
            $action = $this->_getRequest()->getActionName();
            if ($action == 'search' || $action == 'searchProducts') {
                $_price = $product->getData('price');
                try {
                    $_priceBlock = new OrganicInternet_SimpleConfigurableProducts_Catalog_Model_Product_Type_Configurable_Price;
                    $childProduct = $_priceBlock->getChildProductWithLowestPrice($product, "finalPrice");
                    if (!$childProduct) {
                        $childProduct = $_priceBlock->getChildProductWithLowestPrice($product, "finalPrice", false);
                    }
                    $_childBaseprice = $childProduct->getPrice();

                    if ($_price > $_childBaseprice) {
                        return $_price;
                    } else {
                        return $_childBaseprice;
                    }
                } catch (Exception $e) {
                    return $_price;
                }
            } else {
                return $product->getPrice();
            }
        } else {
            return $product->getPrice();
        }
    }

    /**
     * Get image file name from Url
     *
     * @param $url
     * @return string
     */
    protected function _getImageFileName($url)
    {
        return basename($url);
    }

    /**
     * Return product's image
     * @param $product Mage_Catalog_Model_Product
     * @return string
     */
    public function getProductImage($product)
    {
        return $this->_getProductImage($product);
    }
}
