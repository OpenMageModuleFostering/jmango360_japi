<?php

class Jmango360_Japi_Model_Rest_Cms_Page extends Mage_Cms_Model_Page
{
    /**
     * Used for retrieving cms pages
     *
     * @param null|int $storeId
     * @return array
     */
    public function getList($storeId = null)
    {
        $cmsPages = Mage::getModel('cms/page')->getCollection();
        if (!is_null($storeId)) {
            $cmsPages->addStoreFilter($storeId);
        }

        $pagesResult = array();
        $processor = Mage::helper('cms')->getPageTemplateProcessor();
        foreach ($cmsPages as $page) {
            $pagesResult[] = $this->_prepareCmsPageData($page, $processor);
        }

        $data['pages'] = $pagesResult;

        return $data;
    }

    /**
     * Prepares CMS Page Data for returning
     *
     * @param Mage_Cms_Model_Page $page
     * @param Varien_Filter_Template $processor
     * @return mixed
     */
    protected function _prepareCmsPageData(Mage_Cms_Model_Page $page, Varien_Filter_Template $processor)
    {
        $result['title'] = $page->getTitle();
        $result['urlKey'] = $page->getIdentifier();
        $result['active'] = $page->getIsActive();
        $result['created_at'] = $page->getCreationTime();
        $result['updated_at'] = $page->getUpdateTime();
        $result['content'] = $processor->filter($this->_filter($page->getContent()));

        $pageStoreIds = array();
        $_page = Mage::getModel('cms/page')->load($page->getId());
        $page_StoreIds = $_page['store_id'];
        if (is_array($page_StoreIds)) {
            foreach ($page_StoreIds as $storeId) {
                $pageStoreIds[] = $storeId;
            }
        }
        $result['storeIds'] = count($pageStoreIds) ? $pageStoreIds : null;

        return $result;
    }

    /**
     * Remove unsupported text
     *
     * @param $string
     * @return string
     */
    protected function _filter($string)
    {
        return preg_replace(Varien_Filter_Template::CONSTRUCTION_PATTERN, '', $string);
    }
}