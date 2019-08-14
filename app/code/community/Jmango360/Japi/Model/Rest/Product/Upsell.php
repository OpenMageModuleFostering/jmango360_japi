<?php

class Jmango360_Japi_Model_Rest_Product_Upsell extends Jmango360_Japi_Model_Rest_Product
{
    public function getList()
    {
        $product = $this->_initProduct();

        $collection = $product->getUpSellProductCollection()
            ->setPositionOrder()
            ->addStoreFilter();

        if (Mage::helper('catalog')->isModuleEnabled('Mage_Checkout')) {
            Mage::getResourceSingleton('checkout/cart')->addExcludeProductFilter(
                $collection,
                Mage::getSingleton('checkout/session')->getQuoteId()
            );
            $this->_addProductAttributesAndPrices($collection);
        }

        Mage::helper('japi/product')->applyHideOnAppFilter($collection);
        Mage::getSingleton('catalog/product_visibility')->addVisibleInCatalogFilterToCollection($collection);

        $collection->load();

        /**
         * Updating collection with desired items
         */
        Mage::dispatchEvent('catalog_product_upsell', array(
            'product' => $product,
            'collection' => $collection,
            'limit' => 999
        ));

        foreach ($collection as $product) {
            $product->setDoNotUseCategoryId(true);
        }

        /* @var $helper Jmango360_Japi_Helper_Product */
        $helper = Mage::helper('japi/product');

        $data['products'] = $helper->convertProductCollectionToApiResponseV2($collection);

        return $data;
    }

    /**
     * Add all attributes and apply pricing logic to products collection
     * to get correct values in different products lists.
     * E.g. crosssells, upsells, new products, recently viewed
     *
     * @param Mage_Catalog_Model_Resource_Product_Collection $collection
     * @return Mage_Catalog_Model_Resource_Product_Collection
     */
    protected function _addProductAttributesAndPrices(Mage_Catalog_Model_Resource_Product_Collection $collection)
    {
        return $collection
            ->addAttributeToSelect(array('sku'))
            ->addMinimalPrice()
            ->addFinalPrice()
            ->addTaxPercents()
            ->addAttributeToSelect(Mage::getSingleton('catalog/config')->getProductAttributes())
            ->addUrlRewrite();
    }
}