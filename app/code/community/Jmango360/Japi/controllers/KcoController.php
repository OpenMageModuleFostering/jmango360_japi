<?php
include_once('NWT/KCO/controllers/CheckoutController.php');

class Jmango360_Japi_KcoController extends NWT_KCO_CheckoutController
{
    /**
     * Load layout by handles(s)
     *
     * @param   string|null|bool $handles
     * @param   bool $generateBlocks
     * @param   bool $generateXml
     * @return  Mage_Core_Controller_Varien_Action
     */
    public function loadLayout($handles = null, $generateBlocks = true, $generateXml = true)
    {
        if ($handles == 'nwtkco') {
            return parent::loadLayout('japi_kco_index');
        } else {
            return parent::loadLayout($handles, $generateBlocks, $generateXml);
        }
    }

    /**
     * Order success (thankyou) action
     */
    public function thankyouAction()
    {
        $session = $this->_getSession();

        if (!$session->getLastSuccessQuoteId()) {
            $this->_redirect('checkout/cart');
            return;
        }
        $session->setLastQuoteId($session->getLastSuccessQuoteId());

        // This is need by thank you block; klarnaOrder is set in confirmationAction
        Mage::register('KlarnaOrder', $this->_getSession()->getKlarnaOrder());

        //$this->_getSession()->clear();
        $this->_getSession()->unsKlarnaOrderUri(); //unset klarna location
        $this->_getSession()->unsKlarnaOrder(); //unset klarna location

        if (($lastOrderId = $session->getLastOrderId())) {
            //no, is not onepage, do not use it (this is used by google analytics but we have on ga.phtml (see layout xml)
            //Mage::dispatchEvent('checkout_onepage_controller_success_action', array('order_ids' => array($lastOrderId)));
            //dispatch own event
            Mage::dispatchEvent('checkout_nwtkco_controller_success_action', array('order_ids' => array($lastOrderId)));

            // JMango360: Redirect to checkout/onepage/success
            $order = Mage::getModel('sales/order')->load($lastOrderId);
            if ($order->getId()) {
                $this->_getSession()->setLastOrderId($order->getId());
                $this->_getSession()->setLastRealOrderId($order->getIncrementId());
                return $this->_redirect('checkout/onepage/success');
            }
        } else {
            $this->loadLayout();
            $this->_initLayoutMessages('checkout/session');
            $title = Mage::helper('nwtkco')->getThankyouTitle();
            if (!$title) {
                $title = Mage::helper('nwtkco')->getTitle();
            }
            $this->getLayout()->getBlock('head')->setTitle($title ? $title : $this->__('Klarna Checkout'));
            $block = $this->getLayout()->getBlock('google_analytics');
            if ($block) {
                $block->setOrderIds(array($lastOrderId));
            }
            $this->renderLayout();
        }
    }
}
