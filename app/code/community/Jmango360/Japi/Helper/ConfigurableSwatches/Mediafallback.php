<?php

class Jmango360_Japi_Helper_ConfigurableSwatches_Mediafallback extends Mage_ConfigurableSwatches_Helper_Mediafallback
{
    /**
     * Resize specified type of image on the product for use in the fallback and returns the image URL
     * or returns the image URL for the specified image path if present
     *
     * @param Mage_Catalog_Model_Product $product
     * @param string $type
     * @param bool $keepFrame
     * @param string $image
     * @param bool $placeholder
     * @return string|bool
     */
    protected function _resizeProductImage($product, $type, $keepFrame, $image = null, $placeholder = false)
    {
        $hasTypeData = $product->hasData($type) && $product->getData($type) != 'no_selection';
        if ($image == 'no_selection') {
            $image = null;
        }
        if ($hasTypeData || $placeholder || $image) {
            $helper = Mage::helper('catalog/image')
                ->init($product, $type, $image)
                ->keepFrame(($hasTypeData || $image) ? $keepFrame : false)  // don't keep frame if placeholder
            ;

            $size = Mage::getStoreConfig(Mage_Catalog_Helper_Image::XML_NODE_PRODUCT_BASE_IMAGE_WIDTH);
            if ($type == 'small_image') {
                $size = Mage::getStoreConfig(Mage_Catalog_Helper_Image::XML_NODE_PRODUCT_SMALL_IMAGE_WIDTH);
            }
            if (is_numeric($size)) {
                /**
                 * MPLUGIN-2282: Remove constrainOnly
                 */
                $helper->constrainOnly(false)->resize($size);
            }
            return (string)$helper;
        }
        return false;
    }
}