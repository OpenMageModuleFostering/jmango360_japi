<?php

class Jmango360_Japi_Model_Rest_Catalog_Search_Terms
{
    /**
     * Get fast search result
     */
    public function getTerms()
    {
        $helper = Mage::helper('japi');
        /* @var $helper Jmango360_Japi_Helper_Data */
        $request = $helper->getRequest();

        $data['result'] = array();

        if ($request->getParam('q', false)) {
            $data['result'] = $this->_getSuggestData();
        } else {
            $data['result'] = $this->_getSuggestData(true);
        }

        return $data;
    }

    protected function _getSuggestData($isAll = false)
    {
        if ($isAll) {
            $collection = Mage::getResourceModel('catalogsearch/query_collection')
                ->setStoreId(Mage::app()->getStore()->getId());
        } else {
            $collection = Mage::helper('catalogsearch')->getSuggestCollection();
        }

        $query = Mage::helper('catalogsearch')->getQueryText();
        $data = array();
        foreach ($collection as $item) {
            /* @var $item Mage_CatalogSearch_Model_Query */
            $_data = $item->getQueryText();

            if ($item->getQueryText() == $query) {
                array_unshift($data, $_data);
            } else {
                $data[] = $_data;
            }
        }

        return $data;
    }
}