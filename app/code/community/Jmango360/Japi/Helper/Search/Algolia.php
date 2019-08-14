<?php

class Jmango360_Japi_Helper_Search_Algolia extends Algolia_Algoliasearch_Helper_Data
{
    public function getSearchResult($query = null, $storeId = null)
    {
        $storeId = Mage::app()->getStore()->getId();
        $request = Mage::app()->getFrontController()->getRequest();
        $query = $request->getParam('q');
        $limit = (int)$request->getParam('limit', 12);
        $page = (int)$request->getParam('p', 1);
        $defaultIndex = $this->product_helper->getIndexName($storeId);
        $order = $request->getParam('order', $defaultIndex);
        $dir = $request->getParam('dir', 'desc');
        $indexName = $order ? $order : $defaultIndex;
        $facetsConfig = $this->config->getFacets($storeId);
        $facetsLabel = array();
        if (is_array($facetsConfig)) {
            foreach ($facetsConfig as $facet) {
                $facetsLabel[$facet['attribute']] = $facet['label'];
            }
        }
        $filters = array();
        foreach ($request->getParams() as $param => $value) {
            $param = str_replace('_', '.', $param);
            list($newParam) = explode('.', $param);
            if (isset($facetsLabel[$newParam])) {
                $filters[$newParam] = sprintf('%s:%s', $param, $value);
            }
        }
        $params = array(
            'hitsPerPage' => $limit,
            'maxValuesPerFacet' => 1000,
            'attributesToRetrieve' => 'objectID',
            'attributesToHighlight' => '',
            'page' => $page - 1,
            'facets' => array('categories.level0', 'color'),
            'numericFilters' => 'visibility_search=1',
            'facetFilters' => array_values($filters)
        );
        $answer = $this->algolia_helper->query($indexName, $query, $params);

        $data = array('filters' => array(), 'toolbar_info' => array(), 'products' => array());

        if (is_array($answer['facets'])) {
            $facets = $answer['facets'];
            foreach ($facets as $code => $facet) {
                list($newCode) = explode('.', $code);
                if (isset($filters[$newCode])) {
                    continue;
                }
                $filter = array(
                    'name' => isset($facetsLabel[$newCode]) ? $facetsLabel[$newCode] : $newCode,
                    'code' => $code,
                    'items' => array()
                );
                if (is_array($facet)) {
                    foreach ($facet as $itemLabel => $itemCount) {
                        $filter['items'][] = array(
                            'label' => $itemLabel,
                            'value' => $itemLabel,
                            'count' => $itemCount
                        );
                    }
                }
                $data['filters'][] = $filter;
            }
        }

        $data['toolbar_info'] = array(
            'current_page_num' => $page,
            'current_limit' => $limit,
            'last_page_num' => ceil(@$answer['nbHits'] / $limit),
            'available_limit' => null,
            'current_order' => $order,
            'current_direction' => $dir,
            'available_orders' => array(
                $defaultIndex => $this->__('Relevance')
            )
        );

        $sortingIndices = $this->config->getSortingIndices($storeId);
        if (is_array($sortingIndices)) {
            foreach ($sortingIndices as $sortingIndex) {
                $data['toolbar_info']['available_orders'][$sortingIndex['name']] = $sortingIndex['label'];
            }
        }

        $productIds = array();
        if (is_array($answer['hits'])) {
            $hits = $answer['hits'];
            foreach ($hits as $hit) {
                $productIds[] = $hit['objectID'];
            }
        }
        if (count($productIds)) {
            /** @var Jmango360_Japi_Helper_Product $japiHelper */
            $japiHelper = Mage::helper('japi/product');
            /** @var Mage_Catalog_Model_Resource_Product_Collection $collection */
            $collection = Mage::getModel('catalog/product')->getCollection();
            Mage::getSingleton('catalog/product_status')->addVisibleFilterToCollection($collection);
            Mage::getSingleton('catalog/product_visibility')->addVisibleInSiteFilterToCollection($collection);
            $japiHelper->applySupportedProductTypes($collection);
            $japiHelper->applyHideOnAppFilter($collection);
            $collection->addAttributeToFilter('entity_id', array('in' => $productIds));
            $collection->addAttributeToSelect(Mage::getSingleton('catalog/config')->getProductAttributes());
            $collection->getSelect()->order(sprintf("find_in_set(e.entity_id, '%s')", implode(',', $productIds)));

            $data['products'] = $japiHelper->convertProductCollectionToApiResponseV2($collection, false, false);
        } else {
            $data['message'] = $this->__('Your search returns no results.');
        }

        return $data;
    }
}