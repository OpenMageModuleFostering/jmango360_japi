<?php

class Jmango360_Japi_Helper_Product_Options extends Mage_Core_Helper_Abstract
{
    protected $excludeTypes = array('date', 'date_time', 'time');

    /**
     * Retrieve list of product custom options
     *
     * @param Mage_Catalog_Model_Product $product
     * @return array
     */
    public function getOptionList(Mage_Catalog_Model_Product $product)
    {
        $result = array();

        /** @var $option Mage_Catalog_Model_Product_Option */
        foreach ($product->getProductOptionsCollection() as $option) {
            if (in_array($option->getType(), $this->excludeTypes)) continue;
            $result[] = $this->_getOptionInfo($product, $option);
        }

        if ($this->isModuleEnabled('MadeByMouses_DynamicOptions')) {
            $options = $this->getDynamicOptionsFromMadeByMouses();
            if (count($options)) {
                $dynamicOptions = array();
                $i = 0;
                foreach ($options as $optionKey => $option) {
                    $optionData = array(
                        'option_id' => $option['jidx'],
                        'title' => @$option['label'],
                        'type' => 'drop_down',
                        'is_require' => 0,
                        'sort_order' => $i++,
                        'additional_fields' => array()
                    );
                    if (!isset($option['values'][0])) {
                        $optionData['additional_fields'][] = array(
                            'value_id' => 0,
                            'title' => $this->__('Select a value'),
                            'price' => 0,
                            'price_type' => 'fixed',
                            'sku' => null,
                            'sort_order' => 0
                        );
                    }
                    if (is_array($option['values'])) {
                        $j = 0;
                        foreach ($option['values'] as $key => $value) {
                            $optionData['additional_fields'][] = array(
                                'value_id' => $key,
                                'title' => $value,
                                'price' => 0,
                                'price_type' => 'fixed',
                                'sku' => null,
                                'sort_order' => $j++
                            );
                        }
                    }
                    $dynamicOptions[] = $optionData;
                }
                $result = array_merge($result, $dynamicOptions);
            }
        }

        return $result;
    }

    /**
     * Get options from MadeByMouses_DynamicOptions
     * Also convert option_id from string to int (negative) for mobile work
     *
     * @return array
     */
    public function getDynamicOptionsFromMadeByMouses()
    {
        /** @var MadeByMouses_DynamicOptions_Block_List $block */
        $block = Mage::app()->getLayout()->createBlock('dynamicoptions/list');
        $options = $block->getDynamicOptions();
        $i = -1;
        foreach ($options as $key => $option) {
            $options[$key]['jkey'] = $key;
            $options[$key]['jidx'] = $i--;
        }
        return $options;
    }

    /**
     * Get full information about custom option in product
     *
     * @param Mage_Catalog_Model_Product_Option $option
     * @return array
     */
    protected function _getOptionInfo(Mage_Catalog_Model_Product $product, Mage_Catalog_Model_Product_Option $option)
    {
        $optionPriceWithTax = $this->_preparePrice($product, $option->getPrice(), $option->getPriceType());

        $result = array(
            'option_id' => $option->getId(),
            'title' => $option->getTitle(),
            'type' => $option->getType(),
            'is_require' => $option->getIsRequire(),
            'sort_order' => $option->getSortOrder(),
            // additional_fields should be two-dimensional array for all option types
            'additional_fields' => array(
                array(
                    'price' => $optionPriceWithTax,
                    'price_type' => $option->getPriceType(),
                    'sku' => $option->getSku()
                )
            )
        );

        // MPLUGIN-648: Advanced Product Options compatibility
        switch ($option->getType()) {
            case 'swatch':
                $result['type'] = 'drop_down';
                break;
            case 'multiswatch':
                $result['type'] = 'multiple';
                break;
        }

        // Set additional fields to each type group
        switch ($option->getGroupByType()) {
            case Mage_Catalog_Model_Product_Option::OPTION_GROUP_TEXT:
                $result['additional_fields'][0]['max_characters'] = $option->getMaxCharacters();
                break;
            case Mage_Catalog_Model_Product_Option::OPTION_GROUP_FILE:
                $result['additional_fields'][0]['file_extension'] = $option->getFileExtension();
                $result['additional_fields'][0]['image_size_x'] = $option->getImageSizeX();
                $result['additional_fields'][0]['image_size_y'] = $option->getImageSizeY();
                break;
            case Mage_Catalog_Model_Product_Option::OPTION_GROUP_SELECT:
                $result['additional_fields'] = array();
                foreach ($option->getValues() as $value) {
                    $valuePriceWithTax = $this->_preparePrice($product, $value->getPrice(), $value->getPriceType());
                    $result['additional_fields'][] = array(
                        'value_id' => $value->getId(),
                        'title' => $value->getTitle(),
                        'price' => $valuePriceWithTax,
                        'price_type' => $value->getPriceType(),
                        'sku' => $value->getSku(),
                        'sort_order' => $value->getSortOrder()
                    );
                }
                break;
        }

        return $result;
    }

    /**
     * Calculation real price
     *
     * @param Mage_Catalog_Model_Product $product
     * @param float $optionPrice
     * @param string $priceType
     * @return mixed
     */
    protected function _preparePrice(Mage_Catalog_Model_Product $product, $optionPrice, $priceType)
    {
        if (!empty($priceType) && strcasecmp($priceType, 'percent') == 0) {
            $optionPrice = $product->getFinalPrice() * $optionPrice / 100;
        }

        /* @var $helper Jmango360_Japi_Helper_Product */
        $helper = Mage::helper('japi/product');
        $configurableItemPriceWithTax = $helper->calculatePriceIncludeTax($product, $optionPrice, true, false);

        return (string)$configurableItemPriceWithTax;
    }
}