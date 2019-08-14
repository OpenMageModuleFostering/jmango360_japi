<?php

/**
 * Copyright 2015 JMango360
 */
class Jmango360_Japi_Helper_Product_File extends Mage_Core_Helper_Abstract
{
    /**
     * @param Mage_Catalog_Model_Product $product
     * @return null|array
     */
    public function getItems($product)
    {
        if (!$product->getId()) return null;
        $items = $this->_getProductFiles($product->getId());
        if (!$items) return null;
        /* @var MageWorx_Downloads_Helper_Data $helper */
        $helper = Mage::helper('mageworx_downloads');
        if ($helper->getGroupByCategory() || $helper->checkCustomerAccess($items)) {
            $result['label'] = $this->_getTitle($product);
            foreach ($items as $item) {
                $result['items'][] = $this->_getDownloadItem($item);
            }
            return $result;
        }
    }

    protected function _getDownloadItem($item)
    {
        /* @var MageWorx_Downloads_Helper_Data $helper */
        $helper = Mage::helper('mageworx_downloads');
        $data = array();
        $data['name'] = $item->getName();
        $data['url'] = $item->getUrl() ? $item->getUrl() : $helper->getDownloadLink($item);
        $data['icon'] = $helper->getIconUrl($item->getType());
        $data['size'] = $helper->isDisplaySize() ? $helper->prepareFileSize($item->getSize()) : null;
        $data['description'] = $item->getFileDescription();

        return $data;
    }

    protected function _getTitle($product)
    {
        $productDownloadsTitle = trim(Mage::helper('catalog/output')->productAttribute(
            $product,
            $product->getDownloadsTitle(),
            'downloads_title'
        ));

        if ($productDownloadsTitle) {
            return $productDownloadsTitle;
        } else {
            return Mage::helper('mageworx_downloads')->getProductDownloadsTitle();
        }
    }

    protected function _getProductFiles($productId)
    {
        $_helper = Mage::helper('mageworx_downloads');
        $ids = Mage::getResourceSingleton('mageworx_downloads/relation')->getFileIds($productId);

        if (is_array($ids) && $ids) {
            $files = Mage::getResourceSingleton('mageworx_downloads/files_collection');
            $files->addResetFilter()
                ->addFilesFilter($ids)
                ->addStatusFilter()
                ->addCategoryStatusFilter()
                ->addStoreFilter()
                ->addSortOrder();

            $items = $files->getItems();
            foreach ($items as $k => $item) {
                if (!$_helper->checkCustomerGroupAccess($item) && $_helper->isHideFiles()) {
                    unset($items[$k]);
                }
            }

            return $items;
        }

        return false;
    }
}