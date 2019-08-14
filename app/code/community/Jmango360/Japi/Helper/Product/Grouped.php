<?php

class Jmango360_Japi_Helper_Product_Grouped extends Mage_Core_Helper_Abstract
{
    /**
     * Returns items of grouped product according to Api Response format
     *
     * @param Mage_Catalog_Model_Product $product
     * @param $includePrice bool
     * @return array
     */
    public function getGroupedItems(Mage_Catalog_Model_Product $product, $includePrice = false)
    {
        $items = $product->getTypeInstance(true)->getAssociatedProducts($product);

        $result = array();
        $index = 0;
        foreach ($items as $item) {
            /* @var $item Mage_Catalog_Model_Product */
            if (!$item->hasData('hide_in_jm360')) {
                $item->load($item->getId(), array('hide_in_jm360'));
            }
            if (!$item->getData('hide_in_jm360')) {
                $result[] = $this->_convertItemToArray($item, $includePrice, $index++);
            }
        }
        return $result;
    }

    /**
     * Converts item into array according to Api Response format
     *
     * @param Mage_Catalog_Model_Product $item
     * @param $includePrice bool
     * @param $index int
     * @return array
     */
    protected function _convertItemToArray(Mage_Catalog_Model_Product $item, $includePrice = false, $index = null)
    {
        $result = array();

        $result['product_id'] = $item->getEntityId();
        $result['sku'] = $item->getSku();
        $result['name'] = $item->getName();

        /* @var $helper Jmango360_Japi_Helper_Product */
        $helper = Mage::helper('japi/product');

        try {
            $result['showImage'] = Mage::getStoreConfigFlag('japi/jmango_rest_catalog_settings/show_grouped_item_image');
            if ($result['showImage']) {
                $imageType = Mage::getStoreConfig('japi/jmango_rest_gallery_settings/image_default_listing');
                if (!$imageType) $imageType = 'thumbnail';
                /* @var $imageHelper Mage_Catalog_Helper_Image */
                $imageHelper = Mage::helper('catalog/image');
                $size = $helper->getImageSizes();
                $result['image'] = (string)$imageHelper
                    ->init($item, $imageType, $item->getData($imageType))
                    ->resize($size[$imageType]['width'], $size[$imageType]['height']);
            } else {
                $result['image'] = '';
            }
        } catch (Exception $e) {
            Mage::logException($e);
            $result['image'] = '';
        }

        $selectionPriceWithTax = $helper->calculatePriceIncludeTax($item, $item->getFinalPrice());
        $result['price'] = (string)$selectionPriceWithTax;
        if ($includePrice) {
            $result['final_price'] = $selectionPriceWithTax;
            $result['base_price'] = $helper->calculatePriceIncludeTax($item, $item->getPrice());
        }

        $result['is_saleable'] = (int)$item->isSaleable();
        $result['is_available'] = (int)$item->isSaleable();
        $result['position'] = $index !== null ? $index : (int)$item->getPosition();
        $result['qty'] = $item->getQty();
        $result['stock'] = $item->getStockItem() ? $item->getStockItem()->getQty() : null;
        $result['is_in_stock'] = $item->getStockItem() ? (int)$item->getStockItem()->getIsInStock() : null;

        // Load tier price if present
        /* @var $tierPriceHelper Jmango360_Japi_Helper_Product_TierPrice */
        $tierPriceHelper = Mage::helper('japi/product_tierPrice');
        $result['tier_price'] = $tierPriceHelper->getTierPriceInfo($item);

        return $result;
    }
}