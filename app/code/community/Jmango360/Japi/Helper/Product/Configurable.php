<?php

class Jmango360_Japi_Helper_Product_Configurable extends Mage_Core_Helper_Abstract
{
    /**
     * Get allowed attributes
     *
     * @param Mage_Catalog_Model_Product $product
     * @return array
     */
    public function getAllowAttributes(Mage_Catalog_Model_Product $product)
    {
        return $product->getTypeInstance(true)->getConfigurableAttributes($product);
    }

    /**
     * Check if allowed attributes have options
     *
     * @param Mage_Catalog_Model_Product $product
     * @return bool
     */
    public function hasOptions(Mage_Catalog_Model_Product $product)
    {
        $attributes = $this->getAllowAttributes($product);
        if (count($attributes)) {
            foreach ($attributes as $attribute) {
                /** @var Mage_Catalog_Model_Product_Type_Configurable_Attribute $attribute */
                if ($attribute->getData('prices')) {
                    return true;
                }
            }
        }
        return false;
    }

    protected $allowProducts = null;

    /**
     * Get Allowed Products
     *
     * @param Mage_Catalog_Model_Product $product
     * @return array
     */
    public function getAllowProducts(Mage_Catalog_Model_Product $product)
    {
        $products = array();
        $skipSaleableCheck = true;//$this->getSkipSaleableCheck();
        $allProducts = $product->getTypeInstance(true)
            ->getUsedProducts(null, $product);

        foreach ($allProducts as $product) {
            /* @var $product Mage_Catalog_Model_Product */
            if ($product->isSaleable() || $skipSaleableCheck) {
                $products[] = $product;
            }
        }

        return $products;
    }

    /**
     * Used for compatibility wih old versions, magento 1.6 doesn't have Mage_Catalog_Helper_Product::getSkipSaleableCheck()
     *
     * @return bool
     */
    public function getSkipSaleableCheck()
    {
        /* @var $helper Mage_Catalog_Helper_Product */
        $helper = Mage::helper('catalog/product');
        if (method_exists($helper, 'getSkipSaleableCheck')) {
            return $helper->getSkipSaleableCheck();
        }
        return false;
    }

    /**
     * retrieve current store
     *
     * @return Mage_Core_Model_Store
     */
    public function getCurrentStore()
    {
        return Mage::app()->getStore();
    }

    /**
     * Composes configuration for js
     *
     * @param Mage_Catalog_Model_Product $currentProduct
     * @param $includePrice bool
     * @return array
     */
    public function getConfigurableAttributes(Mage_Catalog_Model_Product $currentProduct, $includePrice = false)
    {
        /* @var $helper Jmango360_Japi_Helper_Product */
        $helper = Mage::helper('japi/product');

        $isNeedPrice = false;
        if ($helper->isSCPActive($currentProduct)) {
            $isNeedPrice = true;
        }

        $attributes = array();
        $options = array();

        foreach ($this->getAllowProducts($currentProduct) as $product) {
            /* @var $product Mage_Catalog_Model_Product */
            $productId = $product->getId();

            if ($isNeedPrice) {
                $product->load($product->getId(), 'final_price');
            }

            foreach ($this->getAllowAttributes($currentProduct) as $attribute) {
                $productAttribute = $attribute->getProductAttribute();
                if (!$productAttribute) continue;
                $productAttributeId = $productAttribute->getId();
                $attributeValue = $product->getData($productAttribute->getAttributeCode());

                if (!isset($options[$productAttributeId])) {
                    $options[$productAttributeId] = array();
                }

                if (!isset($options[$productAttributeId][$attributeValue])) {
                    $options[$productAttributeId][$attributeValue] = array();
                }

                $temp = array(
                    'id' => $productId,
                    'sku' => $product->getSku(),
                    'stock' => $product->getStockItem() ? $product->getStockItem()->getQty() : null,
                    'is_in_stock' => $product->getStockItem() ? (int)$product->getStockItem()->getIsInStock() : null,
                    'is_saleable' => (int)$product->isSaleable(),
                    'backorders' => $product->getStockItem() ? (int)$product->getStockItem()->getBackorders() : 0
                    //'stock_indicator' => $helper->getStockIndicator($product)
                );

                if ($isNeedPrice && $includePrice) {
                    $temp['final_price'] = $helper->calculatePriceIncludeTax($product, $product->getFinalPrice());
                }

                $options[$productAttributeId][$attributeValue][] = $temp;
            }
        }

        /**
         * Support Configurable Swatches
         */
        $optionLabels = array();
        if ($this->isModuleEnabled('Mage_ConfigurableSwatches') && Mage::getStoreConfigFlag('configswatches/general/enabled')) {
            $store = Mage::app()->getStore();
            $imageW = Mage::getStoreConfig('japi/jmango_rest_gallery_settings/image_width');
            $store->setConfig(Mage_Catalog_Helper_Image::XML_NODE_PRODUCT_BASE_IMAGE_WIDTH, $imageW);
            /* @var $mediaFallbackHelper Mage_ConfigurableSwatches_Helper_Mediafallback */
            $mediaFallbackHelper = Mage::helper('configurableswatches/mediafallback');
            $imageFallback = $mediaFallbackHelper->getConfigurableImagesFallbackArray($currentProduct, array('image'), true);
            if (is_array($imageFallback['option_labels'])) {
                foreach ($imageFallback['option_labels'] as $option_label => $option) {
                    if (isset($option['configurable_product'][Mage_ConfigurableSwatches_Helper_Productimg::MEDIA_IMAGE_TYPE_BASE])) {
                        $optionLabels[$option_label] = $option['configurable_product'][Mage_ConfigurableSwatches_Helper_Productimg::MEDIA_IMAGE_TYPE_BASE];
                    } else {
                        if (is_array($imageFallback[Mage_ConfigurableSwatches_Helper_Productimg::MEDIA_IMAGE_TYPE_BASE])) {
                            $compatibleProducts = array_intersect(
                                array_keys($imageFallback[Mage_ConfigurableSwatches_Helper_Productimg::MEDIA_IMAGE_TYPE_BASE]),
                                $option['products']
                            );
                            if (count($compatibleProducts)) {
                                $optionLabels[$option_label] = $imageFallback[Mage_ConfigurableSwatches_Helper_Productimg::MEDIA_IMAGE_TYPE_BASE][reset($compatibleProducts)];
                            }
                        }
                    }
                }
            }
        }

        foreach ($this->getAllowAttributes($currentProduct) as $attribute) {
            $productAttribute = $attribute->getProductAttribute();
            if (!$productAttribute) continue;
            $attributeId = $productAttribute->getId();
            $info = array(
                'id' => $productAttribute->getId(),
                'code' => $productAttribute->getAttributeCode(),
                'label' => $attribute->getLabel(),
                'options' => array()
            );

            $optionPrices = array();
            $prices = $attribute->getPrices();

            if (is_array($prices)) {
                foreach ($prices as $value) {
                    if (!$this->_validateAttributeValue($attributeId, $value, $options)) {
                        continue;
                    }
                    $currentProduct->setConfigurablePrice(
                        $this->_preparePrice($currentProduct, $value['pricing_value'], $value['is_percent'])
                    );
                    $currentProduct->setParentId(true);
                    Mage::dispatchEvent(
                        'catalog_product_type_configurable_price',
                        array('product' => $currentProduct)
                    );
                    $configurablePrice = $currentProduct->getConfigurablePrice();

                    if (isset($options[$attributeId][$value['value_index']])) {
                        $productsIndex = $options[$attributeId][$value['value_index']];
                    } else {
                        $productsIndex = array();
                    }

                    /**
                     * Support Configurable Swatches
                     */
                    if ($this->isModuleEnabled('Mage_ConfigurableSwatches') && Mage::getStoreConfigFlag('configswatches/general/enabled')) {
                        $normalizeLabel = Mage_ConfigurableSwatches_Helper_Data::normalizeKey($value['label']);
                    } else {
                        $normalizeLabel = $value['label'];
                    }

                    $info['options'][] = array(
                        'id' => $value['value_index'],
                        'label' => $value['label'],
                        'price' => $configurablePrice,
                        'oldPrice' => $this->_prepareOldPrice($currentProduct, $value['pricing_value'], $value['is_percent']),
                        'image_url' => $this->_getImageUrl($currentProduct, $value['label']),
                        'product_images' => isset($optionLabels[$normalizeLabel]) ? array($optionLabels[$normalizeLabel]) : array(),
                        'products' => $productsIndex,
                    );

                    $optionPrices[] = $configurablePrice;
                }
            }

            if ($this->_validateAttributeInfo($info)) {
                $attributes[] = $info;
            }
        }

        return $attributes;
    }

    /**
     * Get image url form Configurable Swatches
     *
     * @param Mage_Catalog_Model_Product $product
     * @param string $label
     * @return string
     */
    protected function _getImageUrl($product, $label)
    {
        if (!$product || !$label) return '';

        if ($this->isModuleEnabled('Mage_ConfigurableSwatches') && Mage::getStoreConfigFlag('configswatches/general/enabled')) {
            /** @var Mage_ConfigurableSwatches_Helper_Productimg $imgHelper */
            $imgHelper = Mage::helper('configurableswatches/productimg');
            /** @var Mage_ConfigurableSwatches_Helper_Swatchdimensions $dimHelper */
            $dimHelper = Mage::helper('configurableswatches/swatchdimensions');
            $swatchInnerWidth = $dimHelper->getInnerWidth(Mage_ConfigurableSwatches_Helper_Swatchdimensions::AREA_DETAIL);
            $swatchInnerHeight = $dimHelper->getInnerHeight(Mage_ConfigurableSwatches_Helper_Swatchdimensions::AREA_DETAIL);
            $swatchType = null;
            return $imgHelper->getSwatchUrl($product, $label, $swatchInnerWidth, $swatchInnerHeight, $swatchType);
        }

        return '';
    }

    /**
     * Validating of super product option value
     *
     * @param array $attributeId
     * @param array $value
     * @param array $options
     * @return boolean
     */
    protected function _validateAttributeValue($attributeId, &$value, &$options)
    {
        if (isset($options[$attributeId][$value['value_index']])) {
            return true;
        }

        return false;
    }

    /**
     * Validation of super product option
     *
     * @param array $info
     * @return boolean
     */
    protected function _validateAttributeInfo(&$info)
    {
        if (count($info['options']) > 0) {
            return true;
        }
        return false;
    }

    /**
     * Calculation real price
     *
     * @param Mage_Catalog_Model_Product $product
     * @param float $price
     * @param bool $isPercent
     * @return mixed
     */
    protected function _preparePrice(Mage_Catalog_Model_Product $product, $price, $isPercent = false)
    {
        if ($isPercent && !empty($price)) {
            $price = $product->getFinalPrice() * $price / 100;
        }

        $configurableItemPriceWithTax = Mage::helper('japi/product')->calculatePriceIncludeTax($product, $price, false);

        return $this->_registerJsPrice($this->_convertPrice($configurableItemPriceWithTax, true));
    }

    /**
     * Calculation price before special price
     *
     * @param Mage_Catalog_Model_Product $product
     * @param float $price
     * @param bool $isPercent
     * @return mixed
     */
    protected function _prepareOldPrice(Mage_Catalog_Model_Product $product, $price, $isPercent = false)
    {
        if ($isPercent && !empty($price)) {
            $price = $product->getPrice() * $price / 100;
        }

        $configurableItemPriceWithTax = Mage::helper('japi/product')->calculatePriceIncludeTax($product, $price, false);

        return $this->_registerJsPrice($this->_convertPrice($configurableItemPriceWithTax, true));
    }

    /**
     * Replace ',' on '.' for js
     *
     * @param float $price
     * @return string
     */
    protected function _registerJsPrice($price)
    {
        return str_replace(',', '.', $price);
    }

    /**
     * Convert price from default currency to current currency
     *
     * @param float $price
     * @param boolean $round
     * @return float
     */
    protected function _convertPrice($price, $round = false)
    {
        if (empty($price)) {
            return 0;
        }

        $price = $this->getCurrentStore()->convertPrice($price);
        if ($round) {
            $price = $this->getCurrentStore()->roundPrice($price);
        }

        return $price;
    }
}