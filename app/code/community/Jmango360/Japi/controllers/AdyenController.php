<?php
/**
 * Copyright 2017 JMango360
 */

/**
 * Class Jmango360_Japi_AdyenController
 */
class Jmango360_Japi_AdyenController extends Mage_Core_Controller_Front_Action
{
    /**
     * Action for show last order id
     */
    public function pinAction()
    {
        /** @var Mage_Checkout_Model_Session $checkoutSession */
        $checkoutSession = Mage::getSingleton('checkout/session');
        $lastRealOrderId = $checkoutSession->getLastRealOrderId();
        /** @var Mage_Sales_Model_Order $order */
        $order = Mage::getSingleton('sales/order');
        $order->loadByIncrementId($lastRealOrderId);
        if ($order->getId()) {
            $quoteId = $checkoutSession->getLastQuoteId();
            $quote = Mage::getSingleton('sales/quote')->load($quoteId);
            if ($quote->getId()) {
                $checkoutSession->setData('japi_quote_id', $quoteId);
                $quote->setIsActive(true)->save();
            }

            if (Mage::helper('core')->isModuleEnabled('Flagbit_Checkout')) {
                Mage::helper('flagbit_checkout')->storeAddressData('billing', array());
                Mage::helper('flagbit_checkout')->storeAddressData('shipping', array());
                Mage::helper('flagbit_checkout')->storeAddressData('payment', array());
                Mage::helper('flagbit_checkout')->storeAddressData('use_vat_id', null);
                Mage::helper('flagbit_checkout')->storeAddressData('entitled', null);
                Mage::helper('flagbit_checkout')->storeAddressData('customer', null);
            }

            $this->loadLayout();

            $layout = $this->getLayout();
            $content = $layout->getBlock('content');

            $metaBlock = $layout->createBlock('core/text');
            $texts = array(
                sprintf('<meta name="%s" content="%s">', 'order-id', $order->getIncrementId()),
                sprintf('<meta name="%s" content="%s">', 'order-amount', $order->getGrandTotal()),
                sprintf('<meta name="%s" content="%s">', 'order-currency', $order->getOrderCurrency()->getCode()),
                sprintf('<meta name="%s" content="%s">', 'order-amount-format', $order->formatPriceTxt($order->getGrandTotal()))
            );
            $metaBlock->setText(implode('', $texts));
            $content->append($metaBlock, 'order-info');

            $infoBlock = $layout->createBlock('core/text');
            $infoBlock->setText(sprintf('<center>%s</center>', $this->__('Waiting for payment complete...')));
            $content->append($infoBlock, 'last-real-order-id');

            $this->renderLayout();
        }
    }
}
