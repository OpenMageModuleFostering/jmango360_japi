<?php
/**
 * Copyright 2015 JMango360
 */
class Jmango360_Japi_Block_Checkout_Cart_Totals extends Mage_Checkout_Block_Cart_Totals
{
    /**
     * Render total line
     */
    public function renderTotal($total, $area = null, $colspan = 1)
    {
        $code = $total->getCode();
        if ($total->getAs()) {
            $code = $total->getAs();
        }
        $block = $this->_getTotalRenderer($code);

        $baseDir = 'japi/rwd/onepage/review/totals/';
        switch ($code) {
            case 'subtotal':
            case 'shipping':
            case 'tax':
            case 'grand_total':
                $block->setTemplate($baseDir . $code . '.phtml');
                break;
        }

        return $block->setTotal($total)
            ->setColspan($colspan)
            ->setRenderingArea(is_null($area) ? -1 : $area)
            ->toHtml();
    }
}
