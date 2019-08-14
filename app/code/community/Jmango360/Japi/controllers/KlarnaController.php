<?php
/**
 * Copyright 2016 JMango360
 */
include_once('Vaimo/Klarna/controllers/Checkout/KlarnaController.php');

class Jmango360_Japi_KlarnaController extends Vaimo_Klarna_Checkout_KlarnaController
{
    protected function _resetLayout()
    {
        Mage::app()->getStore()->setConfig('payment/vaimo_klarna_checkout/klarna_layout', 1);
        Mage::app()->getStore()->setConfig('payment/vaimo_klarna_checkout/show_login_form', 0);
        Mage::app()->getStore()->setConfig('payment/vaimo_klarna_checkout/enable_auto_focus', 0);
    }

    public function checkoutAction()
    {
        //Reset layout
        $this->_resetLayout();

        if (!$this->_getCart()->hasQuote()) {
            // If recreate_cart_on_failed_validate is set to no, this parameter is not included
            $id = $this->getRequest()->getParam('quote_id');
            if ($id) {
                $order = Mage::getModel('sales/order')->load($id, 'quote_id');
                if ($order && $order->getId()) {
                    if ($order->getState() == Mage_Sales_Model_Order::STATE_PENDING_PAYMENT) {
                        $comment = $this->__('Order created by Validate, but was abandoned');
                        Mage::helper('klarna')->logKlarnaApi($comment . ' (' . $order->getIncrementId() . ')');

                        $order->addStatusHistoryComment($comment);
                        $order->cancel();
                        $order->save();

                        $quoteNew = Mage::getModel('sales/quote');
                        $quoteOld = Mage::getModel('sales/quote')->load($id);

                        $quoteNew->setStoreId($quoteOld->getStoreId())
                            ->merge($quoteOld)
                            ->setKlarnaCheckoutId(NULL)
                            ->collectTotals()
                            ->save();
                        $this->_getSession()->replaceQuote($quoteNew);

                        $comment = $this->__('Canceled order and created new cart from original cart');
                        Mage::helper('klarna')->logKlarnaApi($comment . ' (' . $quoteNew->getId() . ')');

                        $order->addStatusHistoryComment($comment);
                        $order->save();

                        $error = $this->__('Payment cancelled or some error occured. Please try again.');
                        $this->_getSession()->addError($error);

                        $this->_redirectToCart($quoteNew->getStoreId());
                        return;
                    }
                }
            }
        }

        $quote = $this->_getQuote();

        if (!$quote->getId() || !$quote->hasItems()) {
            $this->_getSession()->addError(Mage::helper('checkout')->__('You have no items in your shopping cart.'));
            $quote->setHasError(true);
        }

        if ($quote->getHasError()) {
            foreach ($quote->getMessages() as $message) {
                $this->_getSession()->addError($message->getCode());
            }
        }

        $quote->load($quote->getId());
        $klarna = Mage::getModel('klarna/klarnacheckout');
        $klarna->setQuote($quote, Vaimo_Klarna_Helper_Data::KLARNA_METHOD_CHECKOUT);
        if (!$klarna->getKlarnaCheckoutEnabled()) {
            if (Mage::helper('klarna')->isOneStepCheckout()) {
                $this->_redirect('onestepcheckout');
            } else {
                $this->_redirect('japi/checkout/onepage');
            }
            return;
        }

        if (!$quote->validateMinimumAmount()) {
            $error = Mage::getStoreConfig('sales/minimum_order/error_message') ?
                Mage::getStoreConfig('sales/minimum_order/error_message') :
                Mage::helper('checkout')->__('Subtotal must exceed minimum order amount');

            $this->_getSession()->addError($error);
            $quote->setHasError(true);
        }

        $updateQuote = false;
        if (Mage::helper('klarna')->checkPaymentMethod($quote)) {
            $updateQuote = true;
        }
        if ($this->_checkShippingMethod()) {
            $updateQuote = true;
        }
        if ($this->_checkNewsletter()) {
            $updateQuote = true;
        }

        if ($updateQuote) {
            $quote->collectTotals();
            $quote->save();
        }

        $this->loadLayout();
        $this->_initLayoutMessages(array('customer/session', 'checkout/session'));
        $this->getLayout()->getBlock('head')->setTitle($this->__('Klarna Checkout'));

        $this->_appendPopupUrls();

        $this->renderLayout();
    }

    /**
     * Append popup urls to response header
     */
    protected function _appendPopupUrls()
    {
        $urls = explode("\n", Mage::getStoreConfig('japi/jmango_rest_checkout_settings/klarna_popup_urls'));
        if (!count($urls)) return;

        $urlsString = implode(';', $urls);
        $this->getResponse()->setHeader('Klarna-Popup-Urls', $urlsString, true);

        $head = $this->getLayout()->getBlock('head');
        if (!$head) return;

        $block = $this->getLayout()->createBlock('core/text');
        $block->setText(sprintf('<meta name="%s" content="%s">', 'Klarna-Popup-Urls', $urlsString));
        $head->append($block, 'Klarna-Popup-Urls');
    }

    public function successAction()
    {
        try {
            Mage::helper('klarna')->logKlarnaApi(Vaimo_Klarna_Helper_Data::KLARNA_LOG_START_TAG);
            $revisitedf = false;
            $checkoutId = $this->_getSession()->getKlarnaCheckoutId();
            if (!$checkoutId) {
                $checkoutId = $this->_getSession()->getKlarnaCheckoutPrevId();
                if ($checkoutId) {
                    $revisitedf = true;
                }
            }
            if (!$checkoutId) {
                Mage::helper('klarna')->logKlarnaApi('successAction checkout id is empty, so we do nothing');
                exit(1);
            }
            if (!$revisitedf) {
                Mage::helper('klarna')->logKlarnaApi('successAction checkout id: ' . $checkoutId);
            } else {
                Mage::helper('klarna')->logKlarnaApi('successAction revisited, checkout id: ' . $checkoutId);
            }
            //$quote = Mage::getModel('sales/quote')->load($checkoutId, 'klarna_checkout_id');
            $quote = Mage::helper('klarna')->findQuote($checkoutId);
            if (!$quote || !$quote->getId()) {
                Mage::throwException($this->__('Cart not available. Please try again') . ': ' . $checkoutId . ' revisitedf = ' . $revisitedf);
            }
            $klarna = Mage::getModel('klarna/klarnacheckout');
            $klarna->setQuote($quote, Vaimo_Klarna_Helper_Data::KLARNA_METHOD_CHECKOUT);

        } catch (Exception $e) {
            // Will show empty success page... however unlikely it is to get here, it's not very good
            Mage::helper('klarna')->logKlarnaException($e);
            return $this;
        }

        $canDisplaySuccess = null;
        // Sometimes there is a timeout or incorrect status is given by the call to Klarna,
        // especially when running against test server
        // Now we try 5 times at least, before showing blank page...
        $useCurrentOrderSession = true;
        for ($cnt = 0; $cnt < 5; $cnt++) {
            try {
                $status = $klarna->getCheckoutStatus($checkoutId, $useCurrentOrderSession);
                $canDisplaySuccess =
                    $status == 'checkout_complete' ||
                    $status == 'created' ||
                    $status == 'AUTHORIZED';
                if (!$canDisplaySuccess) {
                    Mage::helper('klarna')->logDebugInfo(
                        'successAction got incorrect status: ' . $status . ' ' .
                        'for klarna order id: ' . $checkoutId . '. ' .
                        'Retrying (' . ($cnt + 1) . ' / 5)'
                    );
                    $useCurrentOrderSession = false; // Reinitiate communication
                } else {
                    break;
                }
            } catch (Exception $e) {
                Mage::helper('klarna')->logKlarnaException($e);
                Mage::helper('klarna')->logDebugInfo(
                    'successAction caused an exception: ' . $e->getMessage() .
                    'Retrying (' . ($cnt + 1) . ' / 5)'
                );
                $useCurrentOrderSession = false; // Reinitiate communication
            }
        }

        try {
            if (!$canDisplaySuccess) {
                Mage::helper('klarna')->logKlarnaApi('successAction ERROR: order not created: ' . $status);
                $error = $this->__('Checkout incomplete, please try again.');
                $this->_getSession()->addError($error);
                $this->_redirectToCart($quote->getStoreId());
                return $this;
            } else {
                Mage::helper('klarna')->logKlarnaApi('successAction displaying success');
            }

            $createOrderOnSuccess = $klarna->getConfigData('create_order_on_success');

            if (!$revisitedf) {
                if ($quote->getId() && $quote->getIsActive()) {
                    // successQuote returns true if successful, a string if failed
                    $createdKlarnaOrder = new Varien_Object($klarna->getActualKlarnaOrderArray());
                    $result = $klarna->successQuote($checkoutId, $createOrderOnSuccess, $createdKlarnaOrder);
                    Mage::helper('klarna')->logKlarnaApi('successQuote result = ' . $result);

                    $order = Mage::getModel('sales/order')->load($quote->getId(), 'quote_id');

                    if ($order && $order->getId()) {
                        Mage::helper('klarna')->logDebugInfo('successQuote successfully created order with no: ' . $order->getIncrementId());
                    }
                }

                $this->_getCart()->unsetData('quote');
                $this->_getSession()->clearHelperData();
                $this->_getSession()->clear();
                $this->_getSession()->setLastQuoteId($quote->getId());
                $this->_getSession()->setLastSuccessQuoteId($quote->getId());
                $order = Mage::getModel('sales/order')->load($quote->getId(), 'quote_id');
                if ($order && $order->getId()) {
                    $this->_getSession()->setLastOrderId($order->getId());
                    $this->_getSession()->setLastRealOrderId($order->getIncrementId());

                    // JMango360: Append order ID to request header
                    $this->getResponse()->setHeader('Last-Real-Order-Id', $order->getIncrementId(), true);
                }
                $this->_getSession()->setKlarnaCheckoutPrevId($checkoutId);
                $this->_getSession()->setKlarnaCheckoutId(''); // This needs to be cleared, to be able to create new orders
                $this->_getSession()->setKlarnaUseOtherMethods(false);
            }

            //$this->loadLayout();
            //$this->_initLayoutMessages('customer/session');
            //$this->getLayout()->getBlock('head')->setTitle($this->__('Klarna Checkout'));

            if ($this->_getSession()->getLastOrderId()) {
                Mage::dispatchEvent('checkout_onepage_controller_success_action', array('order_ids' => array($this->_getSession()->getLastOrderId())));
            }

            // This is KCO specific for the current API... This must find another solution
            if ($block = Mage::app()->getFrontController()->getAction()->getLayout()->getBlock('google_analytics')) {
                $block->setKlarnaCheckoutOrder($klarna->getActualKlarnaOrder());
            }

            //$this->renderLayout();

            Mage::helper('klarna')->logKlarnaApi('successAction displayed success');
            Mage::helper('klarna')->logKlarnaApi(Vaimo_Klarna_Helper_Data::KLARNA_LOG_END_TAG);

            // JMango360: Redirect to checkout/onepage/success
            $this->_redirect('checkout/onepage/success');
        } catch (Exception $e) {
            // Will show empty success page... however unlikely it is to get here, it's not very good
            Mage::helper('klarna')->logKlarnaException($e);
            return $this;
        }
    }

    public function getKlarnaWrapperHtmlAction()
    {
        $this->_resetLayout();

        $layout = (int)$this->getRequest()->getParam('klarna_layout');

        if ($layout == 1 && !empty($layout)) {
            $blockName = 'klarna_sidebar';
        } else {
            $blockName = 'klarna_default';
        }

        $this->loadLayout('japi_klarna_checkout');

        $block = $this->getLayout()->getBlock($blockName);
        $cartHtml = $block->toHtml();

        $result['update_sections'] = array(
            'name' => 'klarna_sidebar',
            'html' => $cartHtml
        );

        $this->getResponse()->setBody(Zend_Json::encode($result));
    }

    public function getKlarnaCheckoutAction()
    {
        $this->_resetLayout();

        $this->loadLayout('japi_klarna_checkout');

        $block = $this->getLayout()->getBlock('checkout');
        $klarnaCheckoutHtml = $block->toHtml();

        $result['update_sections'] = array(
            'name' => 'klarna_checkout',
            'html' => $klarnaCheckoutHtml
        );

        $this->getResponse()->setBody(Zend_Json::encode($result));
    }
}
