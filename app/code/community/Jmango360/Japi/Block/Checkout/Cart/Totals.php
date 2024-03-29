<?php
/**
 * Copyright 2017 JMango360
 */

/**
 * Class Jmango360_Japi_Block_Checkout_Cart_Totals
 */
class Jmango360_Japi_Block_Checkout_Cart_Totals extends Mage_Checkout_Block_Cart_Totals
{
    protected function _getTotalRenderer($code)
    {
        $blockName = $code . '_total_renderer';

        if ($code == 'grand_total') {
            $newCode = 'grandtotal';
        } else {
            $newCode = $code;
        }

        try {
            $block = $this->getLayout()->createBlock("japi/checkout_total_{$newCode}", $blockName);
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
