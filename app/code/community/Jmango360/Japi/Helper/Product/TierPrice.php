<?php

class Jmango360_Japi_Helper_Product_TierPrice extends Mage_Core_Helper_Abstract
{
    /**
     * Returns price info as array (needed for api)
     *
     * @param Mage_Catalog_Model_Product $product
     * @return array
     */
    public function getTierPriceInfo(Mage_Catalog_Model_Product $product)
    {
        if ($this->isModuleEnabled('Magpleasure_Tierprices')) { // Check if extension Amasty 'Magpleasure_Tierprices' is enabled
            $tierPriceResourceMysql = new Magpleasure_Tierprices_Model_Mysql4_Product_Attribute_Backend_Tierprice;
            $priceData = $tierPriceResourceMysql->loadPriceData($product->getId());

            /* @var $_customTierpriceHelper Magpleasure_Tierprices_Helper_Data */
            $_customTierpriceHelper = Mage::helper('tierprices');
            if ($_customTierpriceHelper->confUseFinalPrice()) {
                $productPrice = $product->getFinalPrice();
            } else {
                $productPrice = $product->getPrice();
            }
        } else {
            $priceData = $product->getTierPrice();
            $productPrice = $product->getFinalPrice();
        }

        if (!isset($priceData) || !is_array($priceData)) {
            return array();
        }

        $useEcomwiseTierPrice = $this->isModuleEnabled('Ecomwise_Mshop');
        $result = array();

        foreach ($priceData as $price) {
            if ($this->isModuleEnabled('Magpleasure_Tierprices')) {
                $result[] = $this->_priceToArray($product, $price, $productPrice);
            } else {
                if ($useEcomwiseTierPrice && isset($price['tier_type']) && $price['tier_type'] == 1 && $price['price'] < 100) {
                    $price['price'] = $productPrice - ($productPrice * $price['price']) / 100;
                    $price['website_price'] = $price['price'];
                }
                $result[] = $this->_priceToArray($product, $price, $productPrice);
            }
        }

        return $result;
    }

    /**
     * Converts tier price to api array data
     *
     * @param Mage_Catalog_Model_Product $product
     * @param array $price
     * @param float $productPrice
     * @return array
     */
    protected function _priceToArray(Mage_Catalog_Model_Product $product, $price, $productPrice)
    {
        $result = array(
            'customer_group_id' => $price['cust_group'],
            'website' => $price['website_id'],
            'qty' => $price['price_qty'],
            'price' => $this->_preparePrice($product, $price['price']),
            'savePercent' => $productPrice ? ceil(100 - ((100 / $productPrice) * $price['price'])) : null
        );

        return $result;
    }

    /**
     * Calculation real price
     *
     * @param Mage_Catalog_Model_Product $product
     * @param float $optionPrice
     * @return mixed
     */
    protected function _preparePrice(Mage_Catalog_Model_Product $product, $optionPrice)
    {
        $tierPriceWithTax = $optionPrice;

        // If not Bundle product then calculate tax. Bundle always return percentage
        if ($product->getTypeId() != Mage_Catalog_Model_Product_Type::TYPE_BUNDLE) {
            $taxHelper = Mage::helper('japi/product');
            /* @var $taxHelper Jmango360_Japi_Helper_Product */
            $tierPriceWithTax = $taxHelper->calculatePriceIncludeTax($product, $optionPrice);
        }

        return (string)$tierPriceWithTax;
    }
}