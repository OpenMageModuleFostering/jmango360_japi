<?php

class Jmango360_Japi_Helper_Product extends Mage_Core_Helper_Abstract
{
    const CONFIGURABLE_SCP_TYPE = 'configurable_scp';

    protected $_defaultImagesPaths = array(
        'image' => array(
            'width' => 'japi/jmango_rest_gallery_settings/image_width',
            'height' => 'japi/jmango_rest_gallery_settings/image_height'
        ),
        'small_image' => array(
            'width' => 'japi/jmango_rest_gallery_settings/small_image_width',
            'height' => 'japi/jmango_rest_gallery_settings/small_image_height'
        ),
        'thumbnail' => array(
            'width' => 'japi/jmango_rest_gallery_settings/small_image_width',
            'height' => 'japi/jmango_rest_gallery_settings/small_image_height'
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
        'hide_in_jm360',
        'extra_information'
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
     * @param Mage_Catalog_Model_Product|null $product
     * @return bool
     */
    public function isSCPActive($product = null)
    {
        if ($this->isModuleEnabled('OrganicInternet_SimpleConfigurableProducts')) {
            return true;
        }

        /**
         * MPLUGIN-1734: Support Amasty_Conf
         * Ref change log: https://amasty.com/color-swatches-pro.html
         */
        if ($this->isModuleEnabled('Amasty_Conf')) {
            /* @var $helper Jmango360_Japi_Helper_Data */
            $helper = Mage::helper('japi');
            $amastyConfVersion = $helper->getExtensionVersion('Amasty_Conf');
            if (version_compare($amastyConfVersion, '3.9.0', '<')) {
                return Mage::getStoreConfigFlag('amconf/general/use_simple_price');
            } else {
                if (Mage::getStoreConfig('amconf/general/use_simple_price') == 2) {
                    return true;
                } elseif (Mage::getStoreConfig('amconf/general/use_simple_price') == 1) {
                    if ($product && $product->getId()) {
                        return (bool)$product->getData('amconf_simple_price');
                    }
                }
            }
        }

        if ($this->isModuleEnabled('Ayasoftware_SimpleProductPricing') && Mage::getStoreConfigFlag('spp/setting/enableModule')) {
            return true;
        }

        if ($this->isModuleEnabled('Itonomy_SimpleConfigurable')) {
            return true;
        }

        if (strpos(Mage::getBaseUrl(), 'hetlinnenhuis') !== false) {
            return true;
        }

        if (strpos(Mage::getBaseUrl(), 'arcaplanet') !== false) {
            return true;
        }

        return false;
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
        if ($this->isModuleEnabled('Amasty_Sorting')) {
            $toolBarBlock = Mage::helper('japi')->getBlock('catalog/product_list_toolbar');
        } else {
            $toolBarBlock = Mage::helper('japi')->getBlock('Mage_Catalog_Block_Product_List_Toolbar');
        }

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
            if (!strpos(Mage::getBaseUrl(), 'bloomfashion.nl') && !strpos($_SERVER['HTTP_HOST'], 'motodiffusion')) {
                $collection->getSelect()->order('cat_index_position ' . strtoupper($direction));
                $collection->setOrder('entity_id', 'asc');
            }
        }
        /**
         * MPLUGIN-2154: Fix bloomfashion.nl ignore sort
         */
        if ($field && strpos(Mage::getBaseUrl(), 'bloomfashion.nl') !== false) {
            if ($field == 'price') {
                $collection->setOrder($toolBarBlock->getCurrentOrder(), $toolBarBlock->getCurrentDirection());
                $collection->setOrder('entity_id', 'asc');
            } elseif ($field != 'position') {
                $collection->unshiftOrder($toolBarBlock->getCurrentOrder(), $toolBarBlock->getCurrentDirection());
                $collection->setOrder('entity_id', 'asc');
            }
        }
        /**
         * Fix list order when sort by position
         * Always add sort by 'entity_id' for website https://www.massamarkt.nl/
         */
        if ($field == 'position' && $this->isModuleEnabled('Massamarkt_Core')) {
            $collection->setOrder('entity_id', 'asc');
        }
        if (version_compare(Mage::getVersion(), '1.9.0.0', '>=')) {
            $_ignoreOrder = array('position', 'entity_id');
        } else {
            $_ignoreOrder = array('position', 'entity_id', 'relevance');
        }
        if (strpos($_SERVER['HTTP_HOST'], 'motodiffusion')) {
            array_push(
                $_ignoreOrder,
                array('highlited_product', 'rewardpoints_spend', 'rating_summary')
            );
        }
        if ($this->isModuleEnabled('GGMGastro_Catalog')) {
            array_push(
                $_ignoreOrder,
                array('popularity_by_reviews', 'popularity_by_rating', 'popularity_by_sells')
            );
        }
        if (!in_array($field, $_ignoreOrder)) {
            if ($request->getParam('category_id')) {
                if ($toolBarBlock->getCurrentOrder() != 'position')
                    $collection->setOrder('position', 'asc');
            }
            /**
             * Always add sort by 'entity_id' for website http://www.gopro-mania.nl
             */
            if ($this->isModuleEnabled('Samiflabs_Shopby')) {
                $collection->setOrder('entity_id', 'asc');
            }
            /**
             * Always add sort by 'entity_id' for website http://www.plusman.nl
             */
            if ($this->isModuleEnabled('Plusman_Custom')) {
                $collection->setOrder('entity_id', 'asc');
            }

            /**
             * This code not affect floyd.no product ordering, it crazy
             */
            if (strpos(Mage::getBaseUrl(), 'floyd.no') !== false) {
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
        foreach ($toolBarBlock->getAvailableOrders() as $order => $label) {
            $data['available_orders'][$order] = $this->__($label);
        }

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

        /**
         * MPLUGIN-1888:
         * This codes seem too ricky, for only small benefit is get current sort direction from theme layout
         * So I decided to remove it.
         * Goodbye code, be happy in graveyard!
         */
        /*
        //Check needed to load sort direction
        $session = Mage::getSingleton('core/session');
        if ($session->getData('japi_direction_loaded')) {
            return '';
        }

        //Get sort direction from frontend layout config
        $layout = Mage::app()->getLayout();
        $update = $layout->getUpdate();
        $update->load('catalog_category_layered');

        //MPLUGIN-1413: fix for 'Amasty_Shopby'
        //MPLUGIN-1632: fix for 'GoMage_Navigation'
        //MPLUGIN-1632: fix for 'Kvh_Simpleseo'
        //Because this issue is common, so I decide to include 'head' block permanently
        $layout->addBlock('page/html_head', 'head');

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
        */

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
     * @param array $config
     * @return null|array
     * @throws Jmango360_Japi_Exception
     */
    public function convertProductIdToApiResponseV2($product, $config = array())
    {
        if (!is_numeric($product)) {
            return null;
        }

        /* @var $collection Mage_Catalog_Model_Resource_Product_Collection */
        $collection = Mage::getResourceModel('catalog/product_collection')
            ->setStoreId(Mage::app()->getStore()->getId())
            ->addAttributeToSelect(Mage::getSingleton('catalog/config')->getProductAttributes())
            ->addFieldToFilter('type_id', array('in' => array('simple', 'configurable', 'grouped', 'bundle')))
            //->addMinimalPrice()
            //->addFinalPrice()
            //->addTaxPercents()
            ->addIdFilter($product);

        if (!isset($config['no_apply_hide_on_app'])) {
            $this->applyHideOnAppFilter($collection);
        }

        if (!isset($config['no_apply_price'])) {
            $collection
                ->addMinimalPrice()
                ->addFinalPrice()
                ->addTaxPercents();
        }

        Mage::getSingleton('catalog/product_status')->addVisibleFilterToCollection($collection);
        //Mage::getSingleton('catalog/product_visibility')->addVisibleInCatalogFilterToCollection($collection);

        $result = $this->convertProductCollectionToApiResponseV2($collection, true);
        return count($result) ? array_pop($result) : null;
    }

    /**
     * Apply filter 'hide_in_jm360'
     *
     * @param Mage_Catalog_Model_Resource_Product_Collection $collection
     * @return Mage_Catalog_Model_Resource_Product_Collection|null
     */
    public function applyHideOnAppFilter($collection)
    {
        if (!$collection) return null;

        $collection->addAttributeToFilter(array(
            array('attribute' => 'hide_in_jm360', 'null' => true),
            array('attribute' => 'hide_in_jm360', 'eq' => 0)
        ), null, 'left');

        return $collection;
    }

    /**
     * Apply filter product types: simple, configurable, grouped, bundle
     *
     * @param Mage_Catalog_Model_Resource_Product_Collection $collection
     * @return Mage_Catalog_Model_Resource_Product_Collection|null
     */
    public function applySupportedProductTypes($collection)
    {
        if (!$collection) return null;

        /* @var $resource Mage_Core_Model_Resource */
        $resource = Mage::getSingleton('core/resource');
        $collection->getSelect()
            ->join(
                array('p' => $resource->getTableName('catalog/product')),
                sprintf(
                    'e.entity_id = p.entity_id AND p.type_id IN (%s)',
                    join(',', array('"simple"', '"configurable"', '"grouped"', '"bundle"'))
                ),
                null
            );

        return $collection;
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
     * @param bool $details Is get product details request
     * @param bool $includePrice Should include price in SQL query
     * @return array
     */
    public function convertProductCollectionToApiResponseV2(Mage_Catalog_Model_Resource_Product_Collection $collection, $details = false, $includePrice = true)
    {
        if ($includePrice) {
            $collection->applyFrontendPriceLimitations();
        }

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

        $attributeTag = Mage::getStoreConfig('japi/jmango_rest_catalog_settings/attribute_for_tag');
        if ($attributeTag) {
            $collection->addAttributeToSelect($attributeTag);
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
        /* @var $japiHelper Jmango360_Japi_Helper_Data */
        $japiHelper = Mage::helper('japi');
        if ($japiHelper->isBazaarvoiceEnabled()) {
            /* @var $bvHelper Jmango360_Japi_Helper_Review_Bazaarvoice */
            $bvHelper = Mage::helper('japi/review_bazaarvoice');
            $bvHelper->appendReviews($collection);
            return;
        }

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
     * @param $details bool
     * @return string
     */
    protected function _getProductImage(Mage_Catalog_Model_Product $product, $details = false)
    {
        /* @var $helper Mage_Catalog_Helper_Image */
        $helper = Mage::helper('catalog/image');
        $size = $this->_getImageSizes();

        $imageType = Mage::getStoreConfig('japi/jmango_rest_gallery_settings/image_default_listing');
        if (!$imageType || !array_key_exists($imageType, $this->_defaultImagesPaths)) {
            $imageType = 'small_image';
        }

        if ($details) {
            $imageWidth = !empty($size['image']['width']) ? $size['image']['width'] : 1200;
            $imageHeight = !empty($size['image']['height']) ? $size['image']['height'] : 1200;
        } else {
            $imageWidth = !empty($size[$imageType]['width']) ? $size[$imageType]['width'] : 400;
            $imageHeight = !empty($size[$imageType]['height']) ? $size[$imageType]['height'] : 400;
        }

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
            if (!$product->getData($imageType) || $product->getData($imageType) == 'no_selection') {
                /**
                 * MPLUGIN-1875: get parent image instead
                 */
                if ($parentId = $this->_getRequest()->getParam('parent_id')) {
                    /* @var $parent Mage_Catalog_Model_Product */
                    $parent = Mage::getModel('catalog/product')->load($parentId, array_keys($this->_defaultImagesPaths));
                    $image = (string)$helper->init($parent, $imageType)->resize($imageWidth, $imageHeight);
                } else {
                    $image = (string)$helper->init($product, $imageType)->resize($imageWidth, $imageHeight);
                }
            } else {
                $image = (string)$helper->init($product, $imageType)->resize($imageWidth, $imageHeight);
            }
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
            //'stock_indicator' => $this->getStockIndicator($product),
            'backorders' => $this->_getStocBackorders($product),
            'is_in_stock' => $product->getStockItem() ? (int)$product->getStockItem()->getIsInStock() : null,
            'is_saleable' => (int)$product->isSalable(),
            'is_available' => $this->_getProductAvailable($product),
            'price' => $this->calculatePriceIncludeTax($product, $_basePrice),
            'final_price' => $this->calculatePriceIncludeTax($product, $product->getFinalPrice()),
            'min_price' => $this->calculatePriceIncludeTax($product, $product->getMinPrice()),
            'max_price' => $this->calculatePriceIncludeTax($product, $product->getMaxPrice()),
            'minimal_price' => $this->calculatePriceIncludeTax($product, $product->getMinimalPrice()),
            'image' => $this->_getProductImage($product, $details),
            'sticky_info' => $this->_getStickyInfo($product)
        );

        $result['review_enable'] = $this->_isReviewEnable();
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
                    if ($attributeCode == 'description' && strpos($_SERVER['HTTP_HOST'], 'buyyourwine') !== false) {
                        $html .= $productHelper->productAttribute($product, $product->getData('about'), 'about');
                    } else {
                        $html .= $productHelper->productAttribute($product, $product->getData($attributeCode), $attributeCode);
                    }
                    if ($attributeCode == 'description' && $this->isModuleEnabled('Massamarkt_Core')) {
                        $html .= '<br />' . $productHelper->productAttribute($product, $product->getData('extra_information'), 'extra_information');
                    }
                    /**
                     * MPLUGIN-2275: GGM "short_description", "description"
                     * MPLUGIN-2287: GGM "important" data
                     */
                    if ($attributeCode == 'description' && $this->isModuleEnabled('Flagbit_DynamicTabs')) {
                        $html .= $productHelper->productAttribute($product, $product->getData('technical'), 'technical');
                        $html .= '<br/><b>' . $this->__('Important') . '</b><br/><br/>';
                        $html .= nl2br($productHelper->productAttribute($product, $product->getData('important'), 'important'));
                    }
                    if ($attributeCode == 'short_description' && $this->isModuleEnabled('GGMGastro_Catalog')) {
                        $html = $this->_getCustomHtmlStyle();
                        $html .= $this->_addDeliveryTimeHtmlGGMGastro($product);
                        $html .= $this->_addTaxHtmlGGMGastro($product);
                        if ($product->getBstock() == 1) {
                            $html .= $product->getBstockDescription();
                        } else {
                            if (Mage::app()->getStore()->getId() == 1) {
                                $html .= "
<ul>
<li>Leasing möglich</li>
<li>Bezahlung per Rechnung möglich</li>
<li>12 Monate Gewährleistung & Garantie auf Ersatzteile</li>
<li><a href=\"". $this->_getUrl('service-ggm') ."\" target=\"new\" >14 Tage Rückgaberecht → Mehr Info</a></li>";
                                if (method_exists($product, 'getbestellartikel')) {
                                    if ($product->getbestellartikel() == 1) {
                                        $html .= "<li>Bestellartikel → Bitte beachten Sie die gesonderten Rückgabebedingungen</li>";
                                    }
                                }

                                $html .= "</ul>";
                            }
                        }
                    }

                    /**
                     * MPLUGIN-2284: popcorn.nl show Article number on short_description
                     */
                    if ($attributeCode == 'short_description' && strpos($_SERVER['HTTP_HOST'], 'popcorn.nl') !== false) {
                        $html .= '<strong>' . $productHelper->__("SKU:") . $product->getSku() . '</strong><br />';
                    }
                    /**
                     * JM-250: Support Amasty Brand
                     */
                    if ($attributeCode == 'description' && strpos(Mage::getBaseUrl(), 'abcleksaker') !== false) {
                        $html .= $this->_getAmastyBrand($product);
                    }
                    $result[$attributeCode] = $this->_cleanHtml($html);
                } else {
                    if ($attributeCode == 'description' && strpos($_SERVER['HTTP_HOST'], 'buyyourwine') !== false) {
                        $html = $product->getData('about');
                    }
                    if ($attributeCode == 'short_description' && strpos($_SERVER['HTTP_HOST'], 'popcorn.nl') !== false) {
                        /**
                         * MPLUGIN-2284: popcorn.nl show Article number on short_description
                         */
                        $html = '<strong>' . $productHelper->__("SKU:") . $product->getSku() . '</strong><br />';
                        $html .= $product->getData($attributeCode);
                    } else {
                        $html = $product->getData($attributeCode);
                    }
                    if ($attributeCode == 'description' && $this->isModuleEnabled('Massamarkt_Core')) {
                        $html .= '<br />' . $productHelper->productAttribute($product, $product->getData('extra_information'), 'extra_information');
                    }
                    $result[$attributeCode] = $html;
                }
            }

            if ($attribute->getData('is_wysiwyg_enabled') == 1) {
                $value = $productHelper->productAttribute($product, $product->getData($attributeCode), $attributeCode);
            } else {
                $value = $attribute->getFrontend()->getValue($product);
            }

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
                        /**
                         * MPLUGIN-2275: GGM short_description vs description
                         */
                        if (!$this->isModuleEnabled('Flagbit_DynamicTabs')) {
                            $result['additional_information'][] = array(
                                'label' => $attribute->getStoreLabel(),
                                'value' => $value,
                                'code' => $attributeCode
                            );
                        }
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
        if ($product->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE && $this->isSCPActive($product)) {
            $result['type'] = self::CONFIGURABLE_SCP_TYPE;
            $result['type_id'] = self::CONFIGURABLE_SCP_TYPE;
        }

        $result['required_price_calculation'] = false;
        if ($product->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_SIMPLE) {
            if ($this->isModuleEnabled('Mico_Cmp') && Mage::getStoreConfigFlag('cmp/config/active')) {
                $result['required_price_calculation'] = true;
            }
        } elseif ($product->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE) {
            if ($this->isSCPActive($product)) {
                $result['required_price_calculation'] = true;
            }
        }

        /**
         * API-127: Return "has_required_options" for mobile API filter
         */
        $result['has_required_options'] = false;
        if ($product->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_SIMPLE) {
            foreach ($product->getProductOptionsCollection() as $option) {
                /** @var $option Mage_Catalog_Model_Product_Option */
                if ($option->getIsRequire()) {
                    $result['has_required_options'] = true;
                }
            }
        } else {
            $result['has_required_options'] = true;
        }

        /**
         * MPLUGIN-1750: Support custom product attribute 'mamut_note'
         */
        if (strpos(Mage::getUrl(), 'deleukstetaartenshop') !== false) {
            if (Mage::app()->getLocale()->getLocaleCode() == 'nl_NL') {
                if ($product->getData('mamut_note')) {
                    $mamutNote = $this->_getCustomHtmlStyle();
                    $mamutNote .= $productHelper->productAttribute($product, $product->getData('mamut_note'), 'description');
                    $result['short_description'] = $mamutNote;
                    $result['description'] = $mamutNote;
                }
            }
        }

        return $result;
    }

    protected function _addDeliveryTimeHtmlGGMGastro($product)
    {
        if (!Mage::getStoreConfigFlag('catalog/price/display_delivery_time_on_categories')) {
            return '';
        }
        $website_id = Mage::app()->getWebsite()->getId();
        $pathInfo = Mage::app()->getRequest()->getPathInfo();
        $html = '';
        if ($product->getDeliveryTime()) {
            $helperdelivery = Mage::helper('magesetup');
            if($product->getBstock() == '1'){
                $html = '<p class="delivery-time" style="color: red;">';
                $html .= $helperdelivery->__('Selbstabholung');
                $html .= '</p>';
            }else{
                if($product->getDeliveryTimeSpan()){
                    if($product->getDeliveryTimeSpanDays()){
                        $html = '<p class="delivery-time delivery-time-color-'. $product->getDeliveryTimeColor() . '">';
                        $html .= $helperdelivery->__('Delivery Time') . ': ' . $product->getDeliveryTimeSpan();
                        $html .= '</p>';
                    }else{
                        $html = '<p class="delivery-time delivery-time-color-'. $product->getDeliveryTimeColor() . '">';
                        $html .= $helperdelivery->__('Deliverable') . ': ' . $product->getDeliveryTimeSpan();
                        $html .= '</p>';
                        $html .= '<p class="delivery-time delivery-time-color-'. $product->getDeliveryTimeColor() . '">';
                        $html .= $helperdelivery->__('In stock') . ': ca. ' . $product->getDeliveryTime();
                        $html .= '</p>';
                    }
                }else{
                    if($website_id!=2){
                        $del = $product->getDeliveryTime();

                        if($del[0]!="2"){
                            $html = '<p class="delivery-time delivery-time-color-'. $product->getDeliveryTimeColor() . '">';
                            $html .= $helperdelivery->__('Delivery Time') . ': ' . $product->getDeliveryTime();
                            $html .= '</p>';
                        }else{
                            if($del == "2 - 5 Werktage"){
                                $html = '<p class="delivery-time delivery-time-color-green">';
                                $html .= $helperdelivery->__('Delivery Time') . ': ' . $product->getDeliveryTime();
                                $html .= '</p>';
                            }
                            else {
                                $html = '<p class="delivery-time delivery-time-color-'. $product->getDeliveryTimeColor() . '">';
                                $html .= $helperdelivery->__('Delivery Time') . ': ' . $product->getDeliveryTime();
                                $html .= '</p>';
                            }
                        }
                    }else{
                        $del = $product->getDeliveryTime();

                        if($del[0]!="3"){
                            $html = '<p class="delivery-time delivery-time-color-'. $product->getDeliveryTimeColor() . '">';
                            $html .= $helperdelivery->__('Delivery Time') . ': ' . $product->getDeliveryTime();
                            $html .= '</p>';
                        }else{
                            $html = '<p class="delivery-time" style="color: red;">';
                            $html .= $helperdelivery->__('out of stock, please ask for the delivery time');
                            $html .= '</p>';
                        }
                    }
                }
            }

        }

        return $html;
    }

    protected function _addTaxHtmlGGMGastro($product)
    {
        $html = '';
        try {
            $_priceBlock = new FireGento_MageSetup_Block_Catalog_Product_Price;
            $_priceBlock->setData('product', $product);
            $htmlTemplate = new Mage_Core_Block_Template;
            $htmlTax = $htmlTemplate->setTemplate('magesetup/price_info.phtml')
                ->setFormattedTaxRate($_priceBlock->getFormattedTaxRate())
                ->setIsIncludingTax($_priceBlock->isIncludingTax())
                ->setIsIncludingShippingCosts($_priceBlock->isIncludingShippingCosts())
                ->setIsShowShippingLink($_priceBlock->isShowShippingLink())
                ->setIsShowWeightInfo($_priceBlock->getIsShowWeightInfo())
                ->setFormattedWeight($_priceBlock->getFormattedWeight())
                ->setBstock($_priceBlock->getBstock())
                ->setNoShippingPriceMessage($_priceBlock->getNoShippingPriceMessage())
                ->toHtml();
            $html .= $htmlTax;
        } catch (Exception $e) {
            return $e->getMessage();
        }
        return $html;
    }

    protected function _addLeasingButtonGGMGastro($product)
    {
        $html = '';
        try {
            Mage::register('product', $product);
            $htmlTemplate = new Mage_Core_Block_Template;
            $htmlLeasingButton = $htmlTemplate->setTemplate('flagbit/catalog/product/view/leasing/button.phtml')
                ->toHtml();
            $html .= $htmlLeasingButton;
        } catch (Exception $e) {
            return $e->getMessage();
        }
        return $html;
    }
    /**
     * Get Amasty brand information
     *
     * @param Mage_Catalog_Model_Product $product
     * @return string
     */
    protected function _getAmastyBrand($product)
    {
        $html = '';
        $brandAttr = Mage::getStoreConfig('amshopby/brands/attr', Mage::app()->getStore()->getStoreId());
        $brandAttrValue = $product->getData($brandAttr);
        if ($brandAttr && $brandAttrValue) {
            $brand = false;

            $filters = Mage::getResourceModel('amshopby/filter_collection')->addTitles();
            foreach ($filters as $filter) {
                $code = $filter->getAttributeCode();
                if (!$code || ($code != $brandAttr)) {
                    continue;
                }
                $optionCollection = Mage::getResourceModel('amshopby/value_collection')
                    ->addPositions()
                    ->addValue()
                    ->addFieldToFilter('filter_id', $filter->getId())
                    ->addFieldToFilter('option_id', $brandAttrValue)
                    ->getFirstItem();
                if ($optionCollection) {
                    $brand = $optionCollection;
                }
            }

            if ($brand && $brand->getId()) {
                $img = false;
                if ($brand->getImgMedium()) {
                    $img = $brand->getImgMedium();
                } elseif ($brand->getImgBig()) {
                    $img = $brand->getImgBig();
                } elseif ($brand->getImgSmall()) {
                    $img = $brand->getImgSmall();
                }

                if ($img) {
                    $img = Mage::getBaseUrl('media') . 'amshopby/' . $img;
                }

                $html .= sprintf('<b>%s</b><br/>', $this->__('Brand'));
                if ($img) {
                    $html .= sprintf('<img src="%s"/>', $img);
                } else {
                    $html .= sprintf('<p>%s</p>', $this->escapeHtml($brand->getCurrentTitle()));
                }
                if ($brand->getCurrentDescr()) {
                    $html .= $this->escapeHtml($brand->getCurrentDescr());
                }
            }
        }

        return $html;
    }

    /**
     * Get product tag data
     *
     * @param Mage_Catalog_Model_Product $product
     * @return null|array
     */
    protected function _getStickyInfo($product)
    {
        if (!$product || !$product->getId()) {
            return null;
        }

        $attributeTag = Mage::getStoreConfig('japi/jmango_rest_catalog_settings/attribute_for_tag');
        if ($attributeTag) {
            $attributeModel = $product->getResource()->getAttribute($attributeTag);
            if ($attributeModel->getId() && $product->getData($attributeTag)) {
                return array(
                    'code' => $attributeTag,
                    'label' => $attributeModel->getStoreLabel(),
                    'value' => !$product->getData($attributeTag) ? $attributeModel->getDefaultValue() : $attributeModel->getFrontend()->getValue($product)
                );
            }
        }

        return null;
    }

    protected function _isReviewEnable()
    {
        /** @var $japiHelper Jmango360_Japi_Helper_Data */
        $japiHelper = Mage::helper('japi');
        if ($japiHelper->isBazaarvoiceEnabled()) {
            return true;
        } else {
            /* @var $reviewHelper Jmango360_Japi_Helper_Product_Review */
            $reviewHelper = Mage::helper('japi/product_review');
            return $reviewHelper->isReviewEnable();
        }
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
                if ($attributeCode == 'description' && strpos($_SERVER['HTTP_HOST'], 'buyyourwine') !== false) {
                    $attrContent = $productHelper->productAttribute($product, $product->getData('about'), 'about');
                } else {
                    $attrContent = $productHelper->productAttribute($product, $product->getData($attributeCode), $attributeCode);
                }
                if ($attributeCode == 'description' && $this->isModuleEnabled('Massamarkt_Core')) {
                    $attrContent .= '<br />' . $productHelper->productAttribute($product, $product->getData('extra_information'), 'extra_information');
                }
                if (!$attrContent || $attrContent == '' || trim(strip_tags($attrContent)) == '') {
                    $result[$attributeCode] = '';
                } else {
                    $html = $this->_getCustomHtmlStyle();
                    $html .= $attrContent;
                    $result[$attributeCode] = $this->_cleanHtml($html);
                }
            } else {
                $html = ($attributeCode == 'description' && strpos($_SERVER['HTTP_HOST'], 'buyyourwine') !== false) ? $product->getData('about') : $product->getData($attributeCode);
                if ($attributeCode == 'description' && $this->isModuleEnabled('Massamarkt_Core')) {
                    $html .= '<br />' . $productHelper->productAttribute($product, $product->getData('extra_information'), 'extra_information');
                }
                $result[$attributeCode] = $html;
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
        if ($product->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE && $this->isSCPActive($product)) {
            $result['type'] = self::CONFIGURABLE_SCP_TYPE;
            $result['type_id'] = self::CONFIGURABLE_SCP_TYPE;
        }

        $result['price'] = $this->calculatePriceIncludeTax($product, $this->_getSCPBasePrice($product));

        return $result;
    }

    /**
     * Return "is_available" property for display stock status
     *
     * @param Mage_Catalog_Model_Product $product
     * @return int
     */
    protected function _getProductAvailable(Mage_Catalog_Model_Product $product)
    {
        if ($product->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_GROUPED) {
            $associatedProducts = $product->getTypeInstance(true)->getAssociatedProducts($product);
            return (int)($product->isAvailable() && count($associatedProducts) > 0);
        } else {
            return (int)$product->isAvailable();
        }
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
        if (version_compare(Mage::helper('japi')->getExtensionVersion('MageWorx_Downloads'), '1.4.4', '<=')) return;
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
        /**
         * MPLUGIN-2275: Stylish
         */
        if ($this->isModuleEnabled('Flagbit_DynamicTabs')) {
            $css .= 'table tr th,table tr td{width:50%!important;text-align:left!important}';
        }
        $css .= '</style>';

        return $css;
    }

    /**
     * Remove: &nbsp;
     * Convert: nl2br
     *
     * @param string $html
     * @return string
     */
    protected function _cleanHtml($html)
    {
        if (!$html) return $html;

        $html = str_replace('&nbsp; ', ' ', str_replace('&nbsp;&nbsp;', ' ', $html));
        /**
         * MPLUGIN-2275: Do not mess with webview
         */
        if (!$this->isModuleEnabled('Flagbit_DynamicTabs')) {
            $html = nl2br($html);
        }

        return $html;
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
                    if (!$_imageListingDefault || !array_key_exists($_imageListingDefault, $this->_defaultImagesPaths)) $_imageListingDefault = 'small_image';
                    $images['thumbnail']['url'] = (string)$helper->init($product, $_imageListingDefault)->resize($size['thumbnail']['width'], $size['thumbnail']['height']);
                }
            }
            $images['thumbnail']['label'] = $product->getName();
        } else {
            $_imageListingDefault = Mage::getStoreConfig('japi/jmango_rest_gallery_settings/image_default_listing');
            if (!$_imageListingDefault || !array_key_exists($_imageListingDefault, $this->_defaultImagesPaths)) $_imageListingDefault = 'small_image';

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
     * and convert to store currency, include weee tax if needed
     *
     * @param Mage_Catalog_Model_Product $_product
     * @param float $productFinalPrice
     * @param bool $convertPrice
     * @param bool $includeWeeeTax
     * @return float
     */
    public function calculatePriceIncludeTax(
        Mage_Catalog_Model_Product $_product,
        $productFinalPrice,
        $convertPrice = true,
        $includeWeeeTax = true
    )
    {
        $store = Mage::app()->getStore();

        /**
         * MPLUGIN-1793: Copy logic from price.phtml
         */
        $productFinalPrice = $store->roundPrice($store->convertPrice($productFinalPrice, false, false));

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

        /**
         * MPLUGIN-1715: Add weee tax
         */
        $weeeTaxAmountInclTaxes = 0;
        if ($includeWeeeTax && $taxHelper->isModuleEnabled('Mage_Weee')) {
            /* @var Mage_Weee_Helper_Data $weeeHelper */
            $weeeHelper = Mage::helper('weee');

            if ($_product->getTypeId() == 'bundle') {
                if ($_product->getPriceType() == 1) {
                    if ($weeeHelper->typeOfDisplay($_product, array(0, 1, 4))) {
                        $weeeTaxAmountInclTaxes = $weeeHelper->getAmountForDisplay($_product);
                        if ($weeeHelper->isTaxable()) {
                            $weeeTaxAttributes = $weeeHelper->getProductWeeeAttributesForRenderer($_product, null, null, null, true);
                            $weeeTaxAmountInclTaxes = $weeeHelper->getAmountInclTaxes($weeeTaxAttributes);
                        }
                    }
                }
            } elseif (!$_product->isGrouped()) {
                if ($weeeHelper->typeOfDisplay($_product, array(0, 1, 4))) {
                    $weeeTaxAmountInclTaxes = $weeeHelper->getAmountForDisplay($_product);
                    if ($weeeHelper->isTaxable()) {
                        $weeeTaxAttributes = $weeeHelper->getProductWeeeAttributesForRenderer($_product, null, null, null, true);
                        $weeeTaxAmountInclTaxes = $weeeHelper->getAmountInclTaxes($weeeTaxAttributes);
                    }
                }
            }

            /**
             * MPLUGIN-1793: Copy logic from price.phtml
             */
            $weeeTaxAmountInclTaxes = $store->roundPrice($store->convertPrice($weeeTaxAmountInclTaxes, false, false));
        }

        return $productFinalPrice + $weeeTaxAmountInclTaxes;
    }

    /**
     * Get stock indicator like https://www.bloomfashion.nl
     *
     * @param Mage_Catalog_Model_Product $product
     * @return null|array
     */
    public function getStockIndicator($product)
    {
        if (!$product || !$product->getId()) return null;

        if (strpos(Mage::getUrl(), 'bloomfashion.nl') !== false) {
            return array(
                array(
                    'qty_min' => null,
                    'qty_max' => 0,
                    'label' => $this->__('(niet meer in voorraad)'),
                    'image' => Mage::getDesign()->getSkinUrl('images/stock_grey.jpg')
                ),
                array(
                    'qty_min' => 1,
                    'qty_max' => 1,
                    'label' => $this->__('(laatste stuk in voorraad)'),
                    'image' => Mage::getDesign()->getSkinUrl('images/stock_red.jpg')
                ),
                array(
                    'qty_min' => 2,
                    'qty_max' => 2,
                    'label' => $this->__('(nog enkele stuks)'),
                    'image' => Mage::getDesign()->getSkinUrl('images/stock_orange.jpg')
                ),
                array(
                    'qty_min' => 3,
                    'qty_max' => null,
                    'label' => $this->__('(op voorraad)'),
                    'image' => Mage::getDesign()->getSkinUrl('images/stock_green.jpg')
                )
            );
        }

        return null;
    }

    /**
     * Return the stock level if user manage stock otherwise return -1
     *
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
     * Return product stock backorders setting
     *
     * @param Mage_Catalog_Model_Product $product
     * @return int
     */
    protected function _getStocBackorders($product)
    {
        $manageStock = $product->getStockItem() ? $product->getStockItem()->getManageStock() : null;

        $backorders = 0;
        if ($manageStock) {
            $backorders = $product->getStockItem()->getBackorders();
        }

        return $backorders;
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
                    'url' => (string)$helper
                        ->init($product, 'image', $image->getFile())
                        ->resize($size['image']['width'], $size['image']['height']),
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
        /**
         * MPLUGIN-1760: Support Bazaarvoice
         */
        /* @var $helper Jmango360_Japi_Helper_Data */
        $helper = Mage::helper('japi');
        if ($helper->isBazaarvoiceEnabled()) {
            if ($product->getRatingSummary() && $product->getRatingSummary()->getReviewsCount()) {
                $result['review'] = array(
                    'type' => 'overview',
                    'code' => 'overview',
                    'values' => range(1, $product->getRatingSummary()->getRatingRange()),
                    'review_counter' => $product->getRatingSummary()->getReviewsCount(),
                    'percent' => $product->getRatingSummary()->getRatingSummary()
                );
            } else {
                $result['review'] = null;
            }
            return;
        }

        /**
         * MPLUGIN-1742: Fix duplicate review summary data
         */
        if (!$product->getRatingSummary()
            || strpos(Mage::getBaseUrl(), 'ekonoom') !== false
        ) {
            Mage::getModel('review/review')->getEntitySummary($product, Mage::app()->getStore()->getId());
        }

        /* @var $helper Jmango360_Japi_Helper_Product_Review */
        $helper = Mage::helper('japi/product_review');
        $reviewCount = $helper->getProductReviewCount($product);
        if ($reviewCount) {
            $result['review'] = array(
                'type' => 'overview',
                'code' => 'overview',
                'values' => array('1', '2', '3', '4', '5'),
                'review_counter' => $helper->getProductReviewCount($product),
                'percent' => $helper->getProductReviewSummary($product)
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
     * @return bool
     */
    protected function _isAllowedAttribute($attribute)
    {
        $excludeAttributes = explode(',', Mage::getStoreConfig('japi/jmango_rest_catalog_settings/exclude_attribute_on_details'));
        $excludeAttributes = array_merge($excludeAttributes, $this->_ignoredAttributeCodes);

        return !in_array($attribute->getFrontendInput(), $this->_ignoredAttributeTypes)
            && !in_array($attribute->getAttributeCode(), $excludeAttributes);
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
     * @param $item Mage_Sales_Model_Quote_Item|Varien_Object
     * @param $product Mage_Catalog_Model_Product
     * @return null|array
     */
    public function getCartProductBuyRequest($item, $product)
    {
        if (!$item || !$product) return null;

        if ($item instanceof Mage_Sales_Model_Quote_Item) {
            $optionCollection = Mage::getModel('sales/quote_item_option')->getCollection()->addItemFilter($item);
            $item->setOptions($optionCollection->getOptionsByItem($item));
            $buyRequest = $item->getBuyRequest();
        } else {
            $buyRequest = $item;
        }

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
        } elseif (
            $product->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE
            && $this->isModuleEnabled('Ayasoftware_SimpleProductPricing')
        ) {
            $prices = Mage::helper('spp')->getCheapestChildPrice($product);
            if (is_array($prices)) {
                return $prices['Min']['price'];
            }
            return $product->getPrice();
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

    /**
     * Support Emico_Tweakwise catalog storage engine
     *
     * @param bool $isSearch Is search API
     * @return array
     * @throws Jmango360_Japi_Exception
     */
    public function getProductCollectionFromEmicoTweakwise($isSearch = false)
    {
        if (!$this->isModuleEnabled('Emico_Tweakwise') || !$this->isModuleEnabled('Emico_TweakwiseExport')) {
            throw new Jmango360_Japi_Exception(
                $this->__('Module(s) %s not found.', 'Emico_Tweakwise, Emico_TweakwiseExport'),
                Jmango360_Japi_Model_Request::HTTP_INTERNAL_ERROR
            );
        }

        /* @var $emicoExportHelper Emico_TweakwiseExport_Helper_Data */
        $emicoExportHelper = Mage::helper('emico_tweakwiseexport');

        /* @var $layer Emico_Tweakwise_Model_Catalog_Layer */
        $layer = Mage::getSingleton('emico_tweakwise/catalog_layer');

        $products = $layer->getProducts();
        $ids = array();
        foreach ($products as $product) {
            if ($product instanceof Emico_Tweakwise_Model_Bus_Type_Item) {
                $ids[] = $emicoExportHelper->fromStoreId($product->getId());
            } elseif ($product->getId()) {
                $ids[] = $product->getId();
            }
        }

        if (empty($ids)) {
            $ids[] = 0;
        }

        /* @var $category Mage_Catalog_Model_Category */
        if ($isSearch) {
            $category = Mage::getModel('catalog/category');
        } else {
            $category = Mage::registry('current_category');
        }

        /* @var $productCollection Mage_Catalog_Model_Resource_Product_Collection */
        $productCollection = Mage::getResourceModel('catalog/product_collection')
            ->setStoreId(Mage::app()->getStore()->getId())
            ->addAttributeToSelect(Mage::getSingleton('catalog/config')->getProductAttributes())
            ->addMinimalPrice()
            ->addFinalPrice()
            ->addTaxPercents()
            ->addIdFilter($ids);

        if ($category->getId()) {
            $productCollection->addCategoryFilter($category)
                ->addUrlRewrite($category->getId());
        }

        Mage::getSingleton('catalog/product_status')->addVisibleFilterToCollection($productCollection);
        Mage::getSingleton('catalog/product_visibility')->addVisibleInCatalogFilterToCollection($productCollection);
        $this->applySupportedProductTypes($productCollection);
        $this->applyHideOnAppFilter($productCollection);

        /**
         * Keep collection ordered
         */
        $orderString = array('CASE e.entity_id');
        foreach ($ids as $i => $id) {
            $orderString[] = 'WHEN ' . $id . ' THEN ' . $i;
        }
        $orderString[] = 'END';
        $orderString = implode(' ', $orderString);
        $productCollection->getSelect()->order(new Zend_Db_Expr($orderString));

        if (!$productCollection->getSize()) {
            $data['message'] = Mage::helper('japi')->__($isSearch ? 'Your search returns no results.' : 'No products found.');
        }

        /**
         * Get filters (facets)
         */
        $requestParams = Mage::helper('japi')->getRequest()->getParams();
        foreach ($layer->getFacets() as $facet) {
            if ($facet->getFacetSettings()->getIsVisible()) {
                if (!$facet->isCategory() && !$facet->isTree() && !$facet->isSlider()) {
                    if (count($facet->getAttributes()) > 0) {
                        $facetSettings = $facet->getFacetSettings();
                        $code = $facetSettings->getUrlKey();

                        /**
                         * Simple logic to not support multiselect filter
                         */
                        if (array_key_exists($code, $requestParams)) continue;

                        $filter = array(
                            'name' => Mage::helper('catalog')->__($facetSettings->getTitle()),
                            'code' => $code
                        );
                        foreach ($facet->getAttributes() as $item) {
                            $label = Zend_Filter::filterStatic(
                                $facetSettings->getPrefix() . ' ' . $item->getTitle() . ' ' . $facetSettings->getPostfix(),
                                'StringTrim'
                            );
                            $filter['items'][] = array(
                                'label' => $label,
                                'value' => $item->getTitle(),
                                'count' => $item->getNumberOfResults()
                            );
                        }

                        $data['filters'][] = $filter;
                    }
                }
            }
        }

        /**
         * Get paging data
         */
        $responseProperties = $layer->getTweakwiseResponse()->getProperties();
        $toolbarInfo = array(
            'current_page_num' => $responseProperties->getCurrentPage(),
            'last_page_num' => $responseProperties->getNumberOfPages(),
            'current_limit' => $responseProperties->getPageSize(),
            'available_limit' => null,
            'current_order' => null,
            'current_direction' => null
        );
        foreach ($responseProperties->getSortFields() as $sortField) {
            $toolbarInfo['available_orders'][$sortField->getTitle()] = Mage::helper('catalog')->__($sortField->getDisplayTitle());
            if ($sortField->getIsSelected()) {
                $toolbarInfo['current_order'] = $sortField->getTitle();
                $toolbarInfo['current_direction'] = strtolower($sortField->getOrder());
            }
        }
        if (empty($toolbarInfo['available_orders'])) {
            $toolbarInfo['available_orders'] = null;
        }
        if (empty($toolbarInfo['current_order'])) {
            foreach ($responseProperties->getSortFields() as $sortField) {
                if ($responseProperties->getPageUrl() == $sortField->getUrl()) {
                    $toolbarInfo['current_order'] = $sortField->getTitle();
                    $toolbarInfo['current_direction'] = strtolower($sortField->getOrder());
                }
            }
        }
        $data['toolbar_info'] = $toolbarInfo;

        /**
         * Get products data
         */
        $data['products'] = $this->convertProductCollectionToApiResponseV2($productCollection);

        return $data;
    }

    /**
     * Support Klevu_Search catalog storage engine
     *
     * @return array
     * @throws Jmango360_Japi_Exception
     */
    public function getProductCollectionFromKlevuSearch()
    {
        if (!$this->isModuleEnabled('Klevu_Search')) {
            throw new Jmango360_Japi_Exception(
                $this->__('Module(s) %s not found.', 'Klevu_Search'),
                Jmango360_Japi_Model_Request::HTTP_INTERNAL_ERROR
            );
        }

        /**
         * Init sorting
         */
        /* @var Mage_Catalog_Block_Product_List $productListBlock */
        $productListBlock = Mage::getBlockSingleton('catalog/product_list');
        $toolbarBlock = $productListBlock->getToolbarBlock();
        $order = $this->_getRequest()->getParam('order');
        $dir = $this->_getRequest()->getParam('dir', 'desc');
        if ($order) {
            $toolbarBlock->setDefaultOrder($order);
            $toolbarBlock->setDefaultDirection($dir);
        }

        /**
         * Init filter attributes
         */
        /* @var $layer Mage_CatalogSearch_Model_Layer */
        $layer = Mage::getSingleton('catalogsearch/layer');
        $requestParams = $this->_getRequest()->getParams();
        $filters = array();

        if (array_key_exists('category', $requestParams)) {
            $filters['cat'] = null;
            $requestParams['cat'] = $requestParams['category'];
        }

        $excludedParams = array(
            'SID', 'token', 'order', 'dir', 'p', 'limit', 'q', 'category'
        );

        foreach ($requestParams as $param => $value) {
            if (in_array($param, $excludedParams)) continue;
            $attributeModel = Mage::getModel('eav/config')->getAttribute('catalog_product', $param);
            if ($attributeModel->getId()) {
                $filters[$param] = $attributeModel;
            }
        }

        foreach ($filters as $attributeCode => $attributeModel) {
            switch ($attributeCode) {
                case 'cat':
                    /* @var $filterModel Mage_Catalog_Model_Layer_Filter_Category */
                    $filterModel = Mage::getModel('catalog/layer_filter_category');
                    break;
                case 'price':
                    /* @var $filterModel Mage_Catalog_Model_Layer_Filter_Price */
                    $filterModel = Mage::getModel('catalog/layer_filter_price');
                    $filterModel->setAttributeModel($attributeModel);
                    break;
                default:
                    /* @var $filterModel Mage_Catalog_Model_Layer_Filter_Attribute */
                    $filterModel = Mage::getModel('catalogsearch/layer_filter_attribute');
                    $filterModel->setAttributeModel($attributeModel);
            }
            $filterModel->setLayer($layer);
            $layer->getState()->addFilter(
                Mage::getModel('catalog/layer_filter_item')
                    ->setFilter($filterModel)
                    ->setLabel($requestParams[$attributeCode])
                    ->setValue($requestParams[$attributeCode])
            );
        }

        /**
         * Init product collection
         */
        /* @var $productCollection Klevu_Search_Model_CatalogSearch_Resource_Fulltext_Collection */
        $productCollection = Mage::getResourceModel('catalogsearch/fulltext_collection')
            ->addAttributeToSelect(Mage::getSingleton('catalog/config')->getProductAttributes())
            ->addSearchFilter(Mage::helper('catalogsearch')->getQuery()->getQueryText())
            ->setStore(Mage::app()->getStore())
            ->addMinimalPrice()
            ->addFinalPrice()
            ->addTaxPercents()
            ->addStoreFilter()
            ->addUrlRewrite()
            ->setPageSize($this->_getRequest()->getParam('limit', 12));

        Mage::getSingleton('catalog/product_status')->addVisibleFilterToCollection($productCollection);
        Mage::getSingleton('catalog/product_visibility')->addVisibleInSearchFilterToCollection($productCollection);
        $this->applySupportedProductTypes($productCollection);
        $this->applyHideOnAppFilter($productCollection);

        $data = array();

        if (method_exists($productCollection, 'getKlevuFilters')) {
            /**
             * Get filter items
             */
            $klevuFilters = $productCollection->getKlevuFilters();
        } else {
            /** Fixed klevu search in site www.massamarkt.nl */
            $klevuFilters = $this->getKlevuFilters($productCollection);
        }

        foreach ($klevuFilters as $key => $filter) {
            if (array_key_exists($key, $filters)) continue;
            if ($key == 'category' && array_key_exists('cat', $filters)) continue;

            $item['name'] = $filter['label'];
            $item['code'] = $key;
            $item['items'] = null;
            if ($filter['options']) {
                foreach ($filter['options'] as $option) {
                    $item['items'][] = array(
                        'value' => $option['label'],
                        'label' => $option['label'],
                        'count' => $option['count']
                    );
                }
            }
            $data['filters'][] = $item;
        }

        /**
         * Get toolbar information
         */
        $data['toolbar_info'] = array(
            'current_page_num' => $this->_getRequest()->getParam('p', 1),
            'last_page_num' => ceil($productCollection->getSize() / $productCollection->getPageSize()),
            'current_limit' => $this->_getRequest()->getParam('limit', 12),
            'available_limit' => null,
            'current_order' => null,
            'current_direction' => null,
            'available_orders' => null
        );
        if ($order) {
            $data['toolbar_info']['current_order'] = $order;
            $data['toolbar_info']['current_direction'] = $dir;
        } else {
            $data['toolbar_info']['current_order'] = 'relevance';
            $data['toolbar_info']['current_direction'] = 'desc';
        }

        $data['products'] = $this->convertProductCollectionToApiResponseV2($productCollection);

        return $data;
    }

    /**
     * Get Klevu filters from klevu response
     *
     * @param $productCollection
     * @return array
     */
    public function getKlevuFilters($productCollection)
    {
        $attributes = array();
        $filters = $productCollection->getKlevuResponse()->getData('filters');
        // If there are no filters, return empty array.
        if (empty($filters)) return $attributes;

        foreach ($filters as $filter) {
            $key = (string)$filter['key'];
            $attributes[$key] = array('label' => (string)$filter['label']);
            $attributes[$key]['options'] = array();
            if ($filter['options']) {
                foreach ($filter['options'] as $option) {
                    $attributes[$key]['options'][] = array(
                        'label' => trim((string)$option['name']),
                        'count' => trim((string)$option['count']),
                        'selected' => trim((string)$option['selected'])
                    );
                }
            }
        }

        return $attributes;
    }

    /**
     * Support SolrBridge_Solrsearch search engine
     *
     * @return array
     * @throws Jmango360_Japi_Exception
     */
    public function getProductCollectionFromSolrBridgeSolrsearch()
    {
        if (!$this->isModuleEnabled('SolrBridge_Solrsearch')) {
            throw new Jmango360_Japi_Exception(
                $this->__('Module(s) %s not found.', 'SolrBridge_Solrsearch'),
                Jmango360_Japi_Model_Request::HTTP_INTERNAL_ERROR
            );
        }

        $solrData = $this->_prepareSolrData();

        $documents = array();
        if (isset($solrData['response']['docs'])) {
            $documents = $solrData['response']['docs'];
        }

        $productIds = array();
        if (is_array($documents) && count($documents) > 0) {
            foreach ($documents as $_doc) {
                if (isset($_doc['products_id'])) {
                    $productIds[] = $_doc['products_id'];
                }
            }
        }

        /** @var Mage_Catalog_Model_Resource_Product_Collection $collection */
        $collection = Mage::getModel('catalog/product')->getCollection();
        Mage::getSingleton('catalog/product_status')->addVisibleFilterToCollection($collection);
        Mage::getSingleton('catalog/product_visibility')->addVisibleInSiteFilterToCollection($collection);
        $this->applySupportedProductTypes($collection);
        $this->applyHideOnAppFilter($collection);
        $collection->addAttributeToFilter('entity_id', array('in' => $productIds));
        $collection->addAttributeToSelect(Mage::getSingleton('catalog/config')->getProductAttributes());
        if (method_exists($collection, 'addPriceData')) {
            $collection->addPriceData();
        }
        Mage::helper('solrsearch')->applyInstockCheck($collection);
        $collection->getSelect()->order(sprintf("find_in_set(e.entity_id, '%s')", implode(',', $productIds)));

        $data = array();

        /**
         * Parse filters
         */
        if (isset($solrData['facet_counts']['facet_fields']) && is_array($solrData['facet_counts']['facet_fields'])) {
            /** @var SolrBridge_Solrsearch_Block_Faces $facetHelper */
            $facetHelper = Mage::app()->getLayout()->createBlock('solrsearch/faces');

            $facetsFields = $solrData['facet_counts']['facet_fields'];
            $currentFilters = $this->_getRequest()->getParam('fq');

            foreach ($facetsFields as $facet => $facetItems) {
                if (!is_array($facetItems) || !count($facetItems)) continue;
                if ($facetHelper->isFieldRange($facet)) continue;
                if (strpos($facet, 'price') !== false) continue;

                list($attributeCode, $junk) = explode('_', $facet);
                if (array_key_exists($attributeCode, $currentFilters)) continue;

                if ($facet == 'category_path' || $facet == 'category_facet') {
                    $item = array(
                        'name' => $this->__('Category'),
                        'code' => sprintf('fq[%s]', 'category_id'),
                        'items' => array()
                    );
                    foreach ($facetItems as $itemLabel => $itemCount) {
                        list($categoryName, $categoryId) = explode('/', $itemLabel);

                        if (isset($currentFilters['category_id']) && $currentFilters['category_id'] == $categoryId) {
                            continue;
                        }

                        $item['items'][] = array(
                            'value' => $categoryId,
                            'label' => $categoryName,
                            'count' => $itemCount
                        );
                    }
                } else {
                    $item = array(
                        'name' => $facetHelper->getFacetLabel($facet),
                        'code' => sprintf('fq[%s]', $attributeCode),
                        'items' => array()
                    );
                    foreach ($facetItems as $itemLabel => $itemCount) {
                        $item['items'][] = array(
                            'value' => $itemLabel,
                            'label' => $itemLabel,
                            'count' => $itemCount
                        );
                    }
                }

                $data['filters'][] = $item;
            }
        } else {
            $data['filters'] = null;
        }

        /**
         * Parse toolbar data
         */
        /* @var $toolBarBlock Mage_Catalog_Block_Product_List_Toolbar */
        $toolBarBlock = Mage::helper('japi')->getBlock('catalog/product_list_toolbar');
        $toolBarBlock->setCollection($collection);

        $data['toolbar_info']['current_page_num'] = $toolBarBlock->getCurrentPage();
        $data['toolbar_info']['last_page_num'] = !empty($solrData['response']['numFound'])
            ? ceil($solrData['response']['numFound'] / $toolBarBlock->getLimit())
            : null;
        $data['toolbar_info']['current_limit'] = $toolBarBlock->getLimit();
        $data['toolbar_info']['available_limit'] = $toolBarBlock->getAvailableLimit();
        $data['toolbar_info']['current_order'] = $toolBarBlock->getCurrentOrder();
        $currentDirection = $this->_getCurrentDirection($collection);
        if (!$currentDirection) $currentDirection = $toolBarBlock->getCurrentDirection();
        $data['toolbar_info']['current_direction'] = $currentDirection;
        foreach ($toolBarBlock->getAvailableOrders() as $order => $label) {
            $data['toolbar_info']['available_orders'][$order] = $this->__($label);
        }

        /**
         * Convert products
         */
        $data['products'] = $this->convertProductCollectionToApiResponseV2($collection);

        return $data;
    }

    /**
     * Get data from Solr server
     *
     * @return array
     */
    protected function _prepareSolrData()
    {
        $solrModel = Mage::registry('solrbridge_loaded_solr');

        if ($solrModel) {
            return $solrModel->getSolrData();
        } else {
            $solrModel = Mage::getModel('solrsearch/solr');
            $queryText = Mage::helper('solrsearch')->getParam('q');
            return $solrModel->query($queryText);
        }
    }
}
