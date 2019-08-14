<?php

class Jmango360_Japi_Model_Rest_Product_Crosssell extends Jmango360_Japi_Model_Rest_Product
{
    const LIMIT = 99;

    public function getList()
    {
        $id = $this->_getRequest()->getParam('product_id', null);
        if ($id) {
            return $this->_getListByProduct();
        } else {
            return $this->_getListByCart();
        }
    }

    protected function _getListByProduct()
    {
        $product = $this->_initProduct();

        $collection = $product->getCrossSellProductCollection()
            ->addAttributeToSelect(Mage::getSingleton('catalog/config')->getProductAttributes())
            ->setPositionOrder()
            ->addStoreFilter();

        Mage::helper('japi/product')->applyHideOnAppFilter($collection);
        Mage::getSingleton('catalog/product_visibility')->addVisibleInCatalogFilterToCollection($collection);

        $collection->load();
        foreach ($collection as $product) {
            $product->setDoNotUseCategoryId(true);
        }

        /* @var $helper Jmango360_Japi_Helper_Product */
        $helper = Mage::helper('japi/product');

        $data['products'] = $helper->convertProductCollectionToApiResponseV2($collection);

        return $data;
    }

    protected function _getListByCart()
    {
        $ninProductIds = $this->_getCartProductIds();
        if ($ninProductIds) {
            $lastAdded = (int)$this->_getLastAddedProductId();
            $items = array();
            if ($lastAdded) {
                $collection = $this->_getCollection()->addProductFilter($lastAdded);
                if (!empty($ninProductIds)) {
                    $collection->addExcludeProductFilter($ninProductIds);
                }
                $collection->setPositionOrder()->load();

                foreach ($collection as $item) {
                    $ninProductIds[] = $item->getId();
                    $items[] = $item;
                }
            }

            if (count($items) < self::LIMIT) {
                $filterProductIds = array_merge($this->_getCartProductIds(), $this->_getCartProductIdsRel());
                $collection = $this->_getCollection()
                    ->addProductFilter($filterProductIds)
                    ->addExcludeProductFilter($ninProductIds)
                    ->setPageSize(self::LIMIT - count($items))
                    ->setGroupBy()
                    ->setPositionOrder()
                    ->load();

                foreach ($collection as $item) {
                    $items[] = $item;
                }
            }

            /* @var $helper Jmango360_Japi_Helper_Product */
            $helper = Mage::helper('japi/product');

            $data['products'] = $helper->convertProductCollectionToApiResponseV2($collection);
        } else {
            $data['products'] = array();
        }

        return $data;
    }

    /**
     * Get ids of products that are in cart
     *
     * @return array
     */
    protected function _getCartProductIds()
    {
        $ids = array();
        foreach ($this->getQuote()->getAllItems() as $item) {
            if ($product = $item->getProduct()) {
                $ids[] = $product->getId();
            }
        }

        return $ids;
    }

    /**
     * Retrieve Array of product ids which have special relation with products in Cart
     * For example simple product as part of Grouped product
     *
     * @return array
     */
    protected function _getCartProductIdsRel()
    {
        $productIds = array();
        foreach ($this->getQuote()->getAllItems() as $quoteItem) {
            $productTypeOpt = $quoteItem->getOptionByCode('product_type');
            if ($productTypeOpt instanceof Mage_Sales_Model_Quote_Item_Option
                && $productTypeOpt->getValue() == Mage_Catalog_Model_Product_Type_Grouped::TYPE_CODE
                && $productTypeOpt->getProductId()
            ) {
                $productIds[] = $productTypeOpt->getProductId();
            }
        }

        return $productIds;
    }

    /**
     * Get last product ID that was added to cart and remove this information from session
     *
     * @return int
     */
    protected function _getLastAddedProductId()
    {
        return Mage::getSingleton('checkout/session')->getLastAddedProductId(true);
    }

    /**
     * Get crosssell products collection
     *
     * @return Mage_Catalog_Model_Resource_Eav_Mysql4_Product_Link_Product_Collection
     */
    protected function _getCollection()
    {
        $collection = Mage::getModel('catalog/product_link')->useCrossSellLinks()
            ->getProductCollection()
            ->setStoreId(Mage::app()->getStore()->getId())
            ->addStoreFilter()
            ->setPageSize(self::LIMIT);
        $this->_addProductAttributesAndPrices($collection);

        Mage::helper('japi/product')->applyHideOnAppFilter($collection);
        Mage::getSingleton('catalog/product_status')->addSaleableFilterToCollection($collection);
        Mage::getSingleton('catalog/product_visibility')->addVisibleInCatalogFilterToCollection($collection);
        Mage::getSingleton('cataloginventory/stock')->addInStockFilterToCollection($collection);

        return $collection;
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
            ->addMinimalPrice()
            ->addFinalPrice()
            ->addTaxPercents()
            ->addAttributeToSelect(Mage::getSingleton('catalog/config')->getProductAttributes())
            ->addUrlRewrite();
    }

    /**
     * Get quote instance
     *
     * @return Mage_Sales_Model_Quote
     */
    protected function getQuote()
    {
        return Mage::getSingleton('checkout/session')->getQuote();
    }
}