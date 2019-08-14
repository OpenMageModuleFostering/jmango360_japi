<?php

class Jmango360_Japi_Model_Rest_Product_Search extends Jmango360_Japi_Model_Rest_Catalog_Search_Products
{
    /**
     * Get search result
     */
    public function getList()
    {
        /* @var $searchHelper Mage_CatalogSearch_Helper_Data */
        $searchHelper = Mage::helper('catalogsearch');
        /* @var $query Mage_CatalogSearch_Model_Query */
        $query = $searchHelper->getQuery();
        /* @var $helper Jmango360_Japi_Helper_Product */
        $helper = Mage::helper('japi/product');

        $query->setStoreId(Mage::app()->getStore()->getId());
        $data = array();
        if ($query->getQueryText() != '') {
            /**
             * Support Emico_Tweakwise
             */
            if ($helper->isModuleEnabled('Emico_Tweakwise')) {
                return $helper->getProductCollectionFromEmicoTweakwise(true);
            }

            /**
             * Support Klevu_Search
             */
            if ($helper->isModuleEnabled('Klevu_Search')) {
                if (Mage::helper('klevu_search/config')->isLandingEnabled()) {
                    return $helper->getProductCollectionFromKlevuSearch();
                }
            }

            /**
             * Support SolrBridge_Solrsearch
             */
            if ($helper->isModuleEnabled('SolrBridge_Solrsearch')) {
                return $helper->getProductCollectionFromSolrBridgeSolrsearch();
            }

            /**
             * Support Algolia_Algoliasearch
             */
            if ($helper->isModuleEnabled('Algolia_Algoliasearch')) {
                try {
                    /** @var Algolia_Algoliasearch_Helper_Config $config */
                    $config = Mage::helper('algoliasearch/config');
                    $storeId = Mage::app()->getStore()->getId();
                    if ($config->getApplicationID() && $config->getAPIKey() && $config->isEnabledFrontEnd($storeId)) {
                        /** @var Jmango360_Japi_Helper_Search_Algolia $algoliaHelper */
                        $algoliaHelper = Mage::helper('japi/search_algolia');
                        return $algoliaHelper->getSearchResult();
                    }
                } catch (Exception $e) {

                }
            }

            if ($searchHelper->isMinQueryLength()) {
                $query->setId(0)
                    ->setIsActive(1)
                    ->setIsProcessed(1);
            } else {
                if ($query->getId()) {
                    $query->setPopularity($query->getPopularity() + 1);
                } else {
                    $query->setPopularity(1);
                }

                $query->prepare();
            }

            $block = $this->_getSearchLayerBlock();
            /* @var $productCollection Mage_Catalog_Model_Resource_Product_Collection */
            $productCollection = $block->getLayer()->getProductCollection();
            $helper->applyHideOnAppFilter($productCollection);

            /* @var $resource Mage_Core_Model_Resource */
            $resource = Mage::getSingleton('core/resource');
            $productCollection->getSelect()->join(
                array('p' => $resource->getTableName('catalog/product')),
                sprintf(
                    'e.entity_id = p.entity_id AND p.type_id IN (%s)',
                    join(',', array('"simple"', '"configurable"', '"grouped"', '"bundle"'))
                ),
                null
            );

            if (!$productCollection->getSize()) {
                $data['message'] = $searchHelper->__('Your search returns no results.');
            }

            $data['filters'] = $this->_getFilters($block);
            $helper->addPageSettings($productCollection);
            $data['toolbar_info'] = $helper->getToolbarInfo($productCollection, true);

            $productCollection->clear();
            $data['products'] = $helper->convertProductCollectionToApiResponseV2($productCollection);

            $searchHelper->checkNotes();
            $messages = $searchHelper->getNoteMessages();
            if (!empty($messages)) {
                $data['message'] .= implode("\n", (array)$messages);
            }

            if (!$searchHelper->isMinQueryLength()) {
                $query->save();
            }
        } else {
            throw new Jmango360_Japi_Exception(
                $searchHelper->__('Query cannot be empty.'),
                Jmango360_Japi_Model_Request::HTTP_INTERNAL_ERROR
            );
        }

        return $data;
    }

    /**
     * Get fast search result
     */
    public function getSuggest()
    {
        /* @var $searchHelper Mage_CatalogSearch_Helper_Data */
        $searchHelper = Mage::helper('catalogsearch');
        /* @var $query Mage_CatalogSearch_Model_Query */
        $query = $searchHelper->getQuery();
        $query->setStoreId(Mage::app()->getStore()->getId());
        $data = array();

        if ($query->getQueryText() != '') {
            if ($searchHelper->isMinQueryLength()) {
                $query->setId(0)->setIsActive(1)->setIsProcessed(1);
            } else {
                if ($query->getId()) {
                    $query->setPopularity($query->getPopularity() + 1);
                } else {
                    $query->setPopularity(1);
                }
                $query->prepare();
            }

            $helper = Mage::helper('japi/product');
            /* @var $helper Jmango360_Japi_Helper_Product */
            $block = $this->_getSearchLayerBlock();
            /* @var $productCollection Mage_Catalog_Model_Resource_Product_Collection */
            $productCollection = $block->getLayer()->getProductCollection();
            /* @var $resource Mage_Core_Model_Resource */
            $resource = Mage::getSingleton('core/resource');

            $productCollection->getSelect()->join(
                array('p' => $resource->getTableName('catalog/product')),
                sprintf(
                    'e.entity_id = p.entity_id AND p.type_id IN (%s)',
                    join(',', array('"simple"', '"configurable"', '"grouped"', '"bundle"'))
                ),
                null
            );

            if (!$productCollection->getSize()) {
                $data['message'] = $searchHelper->__('Your search returns no results.');
            }

            $helper->addPageSettings($productCollection);
            $helper->getToolBarInfo($productCollection, true);

            $data['products'] = $helper->convertSuggestProductCollectionToApiResponse($productCollection);

            $searchHelper->checkNotes();
            $messages = $searchHelper->getNoteMessages();
            if (!empty($messages)) {
                $data['message'] .= implode("\n", (array)$messages);
            }

            if (!$searchHelper->isMinQueryLength()) {
                $query->save();
            }
        } else {
            throw new Jmango360_Japi_Exception(
                $searchHelper->__('Query cannot be empty.'),
                Jmango360_Japi_Model_Request::HTTP_INTERNAL_ERROR
            );
        }

        return $data;
    }
}