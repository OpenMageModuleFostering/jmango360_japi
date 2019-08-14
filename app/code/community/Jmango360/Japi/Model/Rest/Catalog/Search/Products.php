<?php

class Jmango360_Japi_Model_Rest_Catalog_Search_Products extends Mage_CatalogSearch_Model_Query
{
    /**
     * Catalog Product collection
     *
     * @var Mage_CatalogSearch_Model_Resource_Fulltext_Collection
     */
    protected $_productCollection;

    /**
     * Get search result
     */
    public function getList()
    {
        /* @var $searchHelper Mage_CatalogSearch_Helper_Data */
        $searchHelper = Mage::helper('catalogsearch');
        /* @var $query Mage_CatalogSearch_Model_Query */
        $query = $searchHelper->getQuery();

        $query->setStoreId(Mage::app()->getStore()->getId());
        $data = array();
        if ($query->getQueryText() != '') {
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

            /**
             * By calling this block the Magento standard template load search layout is simulated
             *   -- this way different implementations of search engines and params will be active
             *   -- without code changes
             */
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

            $data['filters'] = $this->_getFilters($block);
            $helper->addPageSettings($productCollection);
            $data['toolbar_info'] = $helper->getToolBarInfo($productCollection, true);
            /**
             * Add group by product's ID to collection
             */
            if (version_compare(Mage::getVersion(), '1.9.2.1', '>')) {
                $productCollection->getSelect()->group('e.entity_id');
            }
            $productCollection->clear();
            $data['products'] = $helper->convertProductCollectionToApiResponse($productCollection);

            $searchHelper->checkNotes();
            $messages = $searchHelper->getNoteMessages();
            if (!empty($messages)) {
                $data['message'] .= implode("\n", (array)$messages);
            }

            if (!$searchHelper->isMinQueryLength()) {
                $query->save();
            }
        } else {
            throw new Jmango360_Japi_Exception(Mage::helper('japi')->__('Query cannot be empty.'), Jmango360_Japi_Model_Request::HTTP_INTERNAL_ERROR);
        }

        return $data;
    }

    /**
     * @return Mage_CatalogSearch_Block_Layer
     */
    protected function _getSearchLayerBlock()
    {
        /* @var $coreHelper Mage_Core_Helper_Data */
        $coreHelper = Mage::helper('core');

        if ($coreHelper->isModuleEnabled('Smile_ElasticSearch')) {
            return Mage::app()->getLayout()->createBlock('Smile_ElasticSearch_Block_Catalogsearch_Layer');
        } else if ($coreHelper->isModuleEnabled('Amasty_Shopby')) {
            Mage::app()->getRequest()->setModuleName('catalogsearch');
            return Mage::app()->getLayout()->createBlock('Amasty_Shopby_Block_Search_Layer');
        } else {
            return Mage::app()->getLayout()->createBlock('Jmango360_Japi_Block_Catalogsearch_Layer');
        }
    }

    protected function _getFilters($block)
    {
        $data = array();

        $filters = $block->getFilters();

        foreach ($filters as $key => $filter) {
            /* @var $filter Mage_Catalog_Block_Layer_Filter_Abstract */
            if (!$this->_shouldShow($filter)) {
                continue;
            }

            if ($filter->getItemsCount()) {
                $arrFilter = $this->_filterToArray($filter);
                if ($arrFilter != null) {
                    $data[] = $arrFilter;
                }
            }
        }

        return $data;
    }

    protected function _shouldShow(Mage_Catalog_Block_Layer_Filter_Abstract $filter)
    {
        $code = $this->_getFilterCode($filter);

        if (!$code) {
            return false;
        }

        if ($filter->getRequest()->getParam($code)) {
            if ($code != 'cat') {
                return false;
            }
        }

        if ($code == 'price') {
            /* @var $helper Mage_Core_Helper_Data */
            $helper = Mage::helper('core');
            if ($helper->isModuleEnabled('Smile_ElasticSearch')) {
                return false;
            }
        }

        return true;
    }

    /**
     * Converts Mage_Catalog_Model_Layer_Filter_Abstract into array
     *
     * @param Mage_Catalog_Block_Layer_Filter_Abstract $filter
     * @return array
     */
    protected function _filterToArray(Mage_Catalog_Block_Layer_Filter_Abstract $filter)
    {
        $data = array();
        $data['name'] = Mage::helper('japi')->__($filter->getName());
        $data['code'] = $this->_getFilterCode($filter);
        foreach ($filter->getItems() as $item) {
            if ((int)$item->getCount() > 0) {
                $data['items'][] = $this->_itemToArray($item, $data['code']);
            }
        }
        if (empty($data['items'])) {
            return null;
        }
        return $data;
    }

    /**
     * Due to an omission by Magento the Mage_Catalog_Block_Layer_Filter_Category is missing the method _prepareFilter where the attribute_model is set
     * -- this function assumes like the other filter software that the code for the category is "cat"
     */
    protected function _getFilterCode(Mage_Catalog_Block_Layer_Filter_Abstract $filter)
    {
        switch (get_class($filter)) {
            case 'Mage_Catalog_Block_Layer_Filter_Category':
            case 'Jmango360_Japi_Block_Catalog_Layer_Filter_Category':
            case 'Smile_ElasticSearch_Block_Catalog_Layer_Filter_Category':
                return 'cat';
                break;
            default:
                if ($filter->getAttributeModel()) {
                    return (string)$filter->getAttributeModel()->getAttributeCode();
                } else {
                    Mage::log(get_class($filter));
                    return null;
                }
                break;
        }
    }

    /**
     * Converts array Mage_Catalog_Model_Layer_Filter_Item into array
     *
     * @param $item
     * @param $code
     * @return array
     */
    protected function _itemToArray($item, $code)
    {
        /**
         * MPLUGIN-1144:
         * Fix isse filter when installed "Gomage_Navigation"
         * and set Use Friendly URLs to "Yes" in Admin config of this extension
         */
        /* @var $helper Jmango360_Japi_Helper_Product */
        $helper = Mage::helper('japi/product');
        $_label = (string)$item->getLabel();
        $_value = (string)$item->getValue();
        if ($helper->isModuleEnabled('GoMage_Navigation')) {
            $_ignoreAttrCode = array('price');
            if (!in_array($code, $_ignoreAttrCode)) {
                $gomageHelper = Mage::helper('gomage_navigation');
                if ($gomageHelper->isFrendlyUrl()) {
                    $_value = $gomageHelper->formatUrlValue($_label, $_value);
                }
            }
        }

        $data = array();
        $data['count'] = (int)$item->getCount();
        $data['label'] = $_label;
        $data['value'] = $_value;

        return $data;
    }
}