<?php

class Jmango360_Japi_Helper_Product_Downloadable extends Mage_Core_Helper_Abstract
{
    /**
     * Retrieve downloadable product links
     *
     * @param Mage_Catalog_Model_Product $product
     * @return array
     */
    public function getDownloadableLinks(Mage_Catalog_Model_Product $product)
    {
        $linkArr = array();
        $links = $product->getTypeInstance(true)->getLinks($product);
        foreach ($links as $item) {
            $tmpLinkItem = array(
                'link_id' => $item->getId(),
                'title' => $item->getTitle(),
                'price' => $item->getPrice(),
                'number_of_downloads' => $item->getNumberOfDownloads(),
                'is_shareable' => $item->getIsShareable(),
                'link_url' => $item->getLinkUrl(),
                'link_type' => $item->getLinkType(),
                'sample_file' => $item->getSampleFile(),
                'sample_url' => $item->getSampleUrl(),
                'sample_type' => $item->getSampleType(),
                'sort_order' => $item->getSortOrder()
            );
            $file = Mage::helper('downloadable/file')->getFilePath(
                Mage_Downloadable_Model_Link::getBasePath(), $item->getLinkFile()
            );

            if ($item->getLinkFile() && !is_file($file)) {
                Mage::helper('core/file_storage_database')->saveFileToFilesystem($file);
            }

            if ($item->getLinkFile() && is_file($file)) {
                $name = Mage::helper('downloadable/file')->getFileFromPathFile($item->getLinkFile());
                $tmpLinkItem['file_save'] = array(
                    array(
                        'file' => $item->getLinkFile(),
                        'name' => $name,
                        'size' => filesize($file),
                        'status' => 'old'
                    ));
            }
            $sampleFile = Mage::helper('downloadable/file')->getFilePath(
                Mage_Downloadable_Model_Link::getBaseSamplePath(), $item->getSampleFile()
            );
            if ($item->getSampleFile() && is_file($sampleFile)) {
                $tmpLinkItem['sample_file_save'] = array(
                    array(
                        'file' => $item->getSampleFile(),
                        'name' => Mage::helper('downloadable/file')->getFileFromPathFile($item->getSampleFile()),
                        'size' => filesize($sampleFile),
                        'status' => 'old'
                    ));
            }
            if ($item->getNumberOfDownloads() == '0') {
                $tmpLinkItem['is_unlimited'] = 1;
            }
            if ($product->getStoreId() && $item->getStoreTitle()) {
                $tmpLinkItem['store_title'] = $item->getStoreTitle();
            }
            if ($product->getStoreId() ) {
                $tmpLinkItem['website_price'] = $item->getWebsitePrice();
            }
            $linkArr[] = $tmpLinkItem;
        }
        unset($item);
        unset($tmpLinkItem);
        unset($links);

        $samples = $product->getTypeInstance(true)->getSamples($product)->getData();
        return array('links' => $linkArr, 'samples' => $samples);
    }
}