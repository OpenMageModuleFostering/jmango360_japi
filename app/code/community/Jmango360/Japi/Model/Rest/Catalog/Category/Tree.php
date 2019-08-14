<?php

class Jmango360_Japi_Model_Rest_Catalog_Category_Tree extends Mage_Catalog_Model_Category
{
    const MAXTREEDEPTH = 2;
    const MAX_PRODUCT_COUNT = 3;

    /**
     * Retrieve category tree of store
     *
     * @return array
     */
    public function tree()
    {
        $storeId = Mage::helper('japi')->getRequest()->getParam('store_id', null);
        if (!is_null($storeId)) {
            Mage::app()->setCurrentStore($storeId);
        }

        return $this->_getActiveMenuArray();
    }

    /**
     * Retrieve category tree with specific root category
     *
     * @param int $id
     * @return array
     * @throws Jmango360_Japi_Exception
     */
    public function category($id)
    {
        /* @var $category Mage_Catalog_Model_Category */
        $category = Mage::getModel('catalog/category')->load($id);
        if (!$category->getId()) {
            throw new Jmango360_Japi_Exception(
                Mage::helper('japi')->__('No category found.'),
                Jmango360_Japi_Model_Request::HTTP_INTERNAL_ERROR
            );
        }

        $data['categories'] = $this->_getActiveMenuTree(array($category), 0);
        return $data;
    }

    protected function _getActiveMenuArray()
    {
        $rootCategoryId = Mage::app()->getStore()->getRootCategoryId();
        $data['categories'] = array();

        /* @var $category Mage_Catalog_Model_Category */
        $category = Mage::getModel('catalog/category')->load($rootCategoryId);

        if (!$category->getId()) {
            /** Not nesscesary to throw an error, a workaround for Varnish */
            //throw new Jmango360_Japi_Exception('No root category found.', Jmango360_Japi_Model_Request::HTTP_INTERNAL_ERROR);
            return $data;
        }

        $childrenCategories = $this->_getMenuChildren($category);
        if (!$childrenCategories->count()) {
            /** Not nesscesary to throw an error, a workaround for Varnish */
            //throw new Jmango360_Japi_Exception('No active categories found.', Jmango360_Japi_Model_Request::HTTP_INTERNAL_ERROR);
            return $data;
        }

        $data['categories'] = $this->_getActiveMenuTree($childrenCategories);

        return $data;
    }

    /*
     * The childlevels are restricted to two levels
    * -- Menu-item1
    * -- -- Menu sub-item1
    * -- -- Menu sub-item2
    * -- Menu-item2
    * -- -- Menu sub-item1
    * (adjust MAXTREEDEPTH to get more tree depth)
    */
    private function _getActiveMenuTree($categories, $indexLevel = 1)
    {
        $maxTreeDepth = Mage::helper('japi')->getRequest()->getParam('max_tree_depth', null);
        if (is_null($maxTreeDepth)) {
            $maxTreeDepth = self::MAXTREEDEPTH;
        }

        $data = array();
        $index = 0;
        foreach ($categories as $category) {
            /* @var $category Mage_Catalog_Model_Category */

            $data[$index] = $category->getData();
            $data[$index]['thumbnail'] = $this->_getThumbnailUrl($category);
            $data[$index]['image'] = $this->_getImageUrl($category);
            //Backwards compatibility
            $data[$index]['category_id'] = $data[$index]['entity_id'];
            $data[$index]['children'] = array();

            $childrenCategories = $this->_getMenuChildren($category);
            if ($childrenCategories && $childrenCategories->count() && $indexLevel < $maxTreeDepth) {
                $indexLevel++;
                $data[$index]['children'] = $this->_getActiveMenuTree($childrenCategories, $indexLevel);
                --$indexLevel;
            }

            $index++;
        }

        return $data;
    }

    /**
     * Retrieve image URL
     *
     * @param $category Mage_Catalog_Model_Category
     * @return string
     */
    protected function _getImageUrl($category)
    {
        if ($image = $category->getImage()) {
            return Mage::getBaseUrl('media') . 'catalog/category/' . urlencode($image);
        }
        return '';
    }

    /**
     * Retrieve thumbnail URL
     *
     * @param $category Mage_Catalog_Model_Category
     * @return string
     */
    protected function _getThumbnailUrl($category)
    {
        if ($image = $category->getData('thumbnail')) {
            return Mage::getBaseUrl('media') . 'catalog/category/' . urlencode($image);
        } else {
            if (!$category->getImageUrl()) {
                return $this->_getImageFromChildrenProduct($category);
            }
        }
        return '';
    }

    /**
     * Get image from first child product, if not get the next
     *
     * @param $category Mage_Catalog_Model_Category
     * @return string
     */
    protected function _getImageFromChildrenProduct($category)
    {
        $imageType = Mage::getStoreConfig('japi/jmango_rest_gallery_settings/catalog_image_default');
        if (!$imageType) $imageType = 'thumbnail';
        if ($imageType == 'none') return '';

        /* @var $productCollection Mage_Catalog_Model_Resource_Product_Collection */
        $productCollection = $category->getProductCollection();
        $productCollection->setPageSize(3)->setCurPage(1);
        Mage::getSingleton('catalog/product_status')->addVisibleFilterToCollection($productCollection);
        Mage::getSingleton('catalog/product_visibility')->addVisibleInCatalogFilterToCollection($productCollection);
        if (!$productCollection->getSize()) return '';

        $imageAttributes = array('image', 'small_image', 'thumbnail');
        $imageSizeW = Mage::getStoreConfig('japi/jmango_rest_gallery_settings/small_image_width');
        $imageSizeH = Mage::getStoreConfig('japi/jmango_rest_gallery_settings/small_image_height');

        $productCollection->addAttributeToSelect($imageAttributes);

        /* @var $catalogHelper Mage_Catalog_Helper_Image */
        $catalogHelper = Mage::helper('catalog/image');

        $url = '';
        $ids = array();
        foreach ($productCollection as $product) {
            /* @var $product Mage_Catalog_Model_Product */
            $ids[] = $product->getId();

            if (!$product->getData($imageType) || $product->getData($imageType) == 'no_selection') {
                continue;
            }

            $url = (string)$catalogHelper->init($product, $imageType)->resize($imageSizeW, $imageSizeH);
            break;
        }

        // log for debugging
        if (Mage::getStoreConfigFlag('japi/jmango_rest_developer_settings/enable')) {
            Mage::log(sprintf("%d: %s", $category->getId(), implode(',', $ids)), null, 'japi_category_thumbnail.log');
        }

        return $url;
    }

    /**
     * @param $category Mage_Catalog_Model_Category
     * @return Mage_Catalog_Model_Resource_Category_Collection
     */
    protected function _getMenuChildren($category)
    {
        /* @var $childrenCategories Mage_Catalog_Model_Resource_Category_Collection */
        $childrenCategories = $category->getCollection()
            ->addAttributeToSelect(array('name', 'is_anchor', 'image', 'thumbnail'))
            ->addAttributeToFilter('is_active', 1)
            ->addIdFilter($category->getChildren())
            ->setOrder('position', Varien_Db_Select::SQL_ASC);

        if (!Mage::getStoreConfigFlag('japi/jmango_rest_catalog_settings/include_all_active')) {
            $childrenCategories->addAttributeToSelect('include_in_menu');
            $childrenCategories->addAttributeToFilter('include_in_menu', array('eq' => 1));
        }

        return $childrenCategories;
    }
}