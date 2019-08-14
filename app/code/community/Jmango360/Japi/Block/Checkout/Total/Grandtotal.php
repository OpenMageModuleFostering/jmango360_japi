<?php
/**
 * Copyright 2017 JMango360
 */

/**
 * Class Jmango360_Japi_Block_Checkout_Total_Grandtotal
 */
class Jmango360_Japi_Block_Checkout_Total_Grandtotal extends Mage_Tax_Block_Checkout_Grandtotal
{
    protected $_template = 'japi/checkout/onepage/review/totals/grand_total.phtml';

    protected function _getTotalRenderer($code)
    {
        $blockName = $code . '_total_renderer';

        try {
            $block = $this->getLayout()->createBlock("japi/checkout_total_{$code}", $blockName);
        } catch (Exception $e) {
            $block = null;
        }

        if (!$block) {
            $block = $this->_defaultRenderer;
            $config = Mage::getConfig()->getNode("global/sales/quote/totals/{$code}/renderer");
            if ($config) {
                $block = (string)$config;
            }

            $block = $this->getLayout()->createBlock($block, $blockName);
        }

        /**
         * Transfer totals to renderer
         */
        $block->setTotals($this->getTotals());

        return $block;
    }
}
