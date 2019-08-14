<?php

class Jmango360_Japi_ImageController extends Mage_Core_Controller_Front_Action
{
    /**
     * Controller entry point
     */
    public function indexAction()
    {

        $storeId = $this->getRequest()->getParam('store');
        $productId = $this->getRequest()->getParam('id');
        $fileName = $this->getRequest()->getParam('file');
        $height = $this->getRequest()->getParam('h');
        $width = $this->getRequest()->getParam('w');
        $size = $this->getRequest()->getParam('size');

        $product = Mage::helper('japi/product')->getProduct($productId, $storeId);

        // Check if width is numeric
        if($this->_isInteger($width) == false) {
            $width = null;
        }

        if($this->_isInteger($height) == false) {
            $height = null;
        }

        if($this->_isInteger($size) == false) {
            $size = null;
        }

        // Size will has more priority and height is not required
        if (!is_null($size) && isset($size)) {
            $width = $size;
            $height = null;
        }

        // For Android client, it might append '?size=xxx' at the end of URL
        $appendValue = '?size=';
        $sizePos = strpos($fileName, $appendValue);
        if ($sizePos !== false) {

            // get length value '?size='
            $sizeLength = strlen($appendValue);

            // Get size actual xxx value
            $sizeVal = substr($fileName, $sizePos + $sizeLength);

            // Check and use it as width if it is correct value
            if($this->_isInteger($sizeVal) == true) {
                $width = $sizeVal;
                $height = null;
            }

            // Need to remove the append value
            $fileName = substr($fileName, 0, $sizePos);

        }

        // Get image cached
        $imageUrl = $this->_getCachedImage($product, $fileName, $width, $height);


        // Redirect image
        Mage::app()->getFrontController()->getResponse()->setRedirect($imageUrl);

    }

    /**
     * Get cached image
     *
     * @param Mage_Catalog_Model_Product $product
     * @param $imageFile
     * @param int $width
     * @param int $height
     *
     * @return mixed
     */
    protected function _getCachedImage(Mage_Catalog_Model_Product $product, $imageFile, $width = null, $height = null) {

        $imageHelper = Mage::helper('catalog/image')->init($product, 'image', $imageFile)
            ->setQuality(100)
            ->constrainOnly(true)
            ->keepAspectRatio(true)
            ->keepFrame(false)
            ->setWatermarkImageOpacity(0);


        $cachedImageUrl = null;


        // get image path  & create image object
        $imagePath = Mage::getModel('catalog/product_media_config')->getMediaPath($imageFile);
        $image = new Varien_Image($imagePath);

        // Resize image and only support scale down
        if ($width != null && $width < $image->getOriginalWidth()) {
            $imageHelper->resize($width, $height);
        }

        $cachedImageUrl = $imageHelper->__toString();

       return $cachedImageUrl;
    }

    /**
     * Check numeric value
     * @param $input
     * @return bool
     */
    protected function _isInteger($input){
        return(ctype_digit(strval($input)));
    }
}