<?php
/**
 * Copyright 2015 JMango360
 */
include_once('Mage/Checkout/controllers/OnepageController.php');

class Jmango360_Japi_CheckoutController extends Mage_Checkout_OnepageController
{
    /**
     * Make sure customer is valid, if logged in
     * By default will add error messages and redirect to customer edit form
     *
     * @param bool $redirect - stop dispatch and redirect?
     * @param bool $addErrors - add error messages?
     * @return bool
     */
    protected function _preDispatchValidateCustomer($redirect = true, $addErrors = true)
    {
        $customer = Mage::getSingleton('customer/session')->getCustomer();
        if ($customer && $customer->getId()) {
            $validationResult = $customer->validate();
            if ((true !== $validationResult) && is_array($validationResult)) {
                Mage::getSingleton('customer/session')->setCheckoutReferer(true);
                if ($addErrors) {
                    foreach ($validationResult as $error) {
                        Mage::getSingleton('customer/session')->addError($error);
                    }
                }
                if ($redirect) {
                    $this->_redirect('japi/customer/edit', array('_secure' => true));
                    $this->setFlag('', self::FLAG_NO_DISPATCH, true);
                }
                return false;
            }
        }
        return true;
    }

    /**
     * Send Ajax redirect response
     *
     * @return Mage_Checkout_OnepageController
     */
    protected function _ajaxRedirectResponse()
    {
        $this->getResponse()
            ->setHeader('Content-Type', 'application/json')
            ->setBody(Mage::helper('core')->jsonEncode(array(
                'error' => true,
                'message' => $this->__('Session exprired.')
            )));
        return $this;
    }

    /**
     * Validate ajax request and redirect on failure
     */
    protected function _expireAjax()
    {
        if (!$this->getOnepage()->getQuote()->hasItems()
            || $this->getOnepage()->getQuote()->getHasError()
            || $this->getOnepage()->getQuote()->getIsMultiShipping()
        ) {
            $this->_ajaxRedirectResponse();
            return true;
        }

        return false;
    }

    /**
     * @return Mage_Checkout_Model_Session
     */
    protected function _getSesstion()
    {
        return Mage::getSingleton('checkout/session');
    }

    /**
     * Check can page show for unregistered users
     *
     * @return boolean
     */
    protected function _allowGuestCheckout()
    {
        return Mage::getSingleton('customer/session')->isLoggedIn()
        || Mage::helper('checkout')->isAllowedGuestCheckout($this->getOnepage()->getQuote());
    }

    /**
     * Redirect to native web checkout url
     */
    public function redirectAction()
    {
        /* @var $helper Jmango360_Japi_Helper_Data */
        $helper = Mage::helper('japi');

        /**
         * MPLUGIN-1377: Reset PHPSESSID
         */
        $helper->checkValidSession();

        $checkoutUrl = $helper->getCheckoutUrl();
        if ($checkoutUrl) $this->_redirectUrl($checkoutUrl);
        else $this->_redirect('checkout/onepage', array('_secure' => true));
    }

    /**
     * New onepage checkout page
     */
    public function onepageAction()
    {
        /* @var $server Jmango360_Japi_Model_Server */
        $server = Mage::getSingleton('japi/server');
        $server->setIsRest();

        $quote = $this->getOnepage()->getQuote();
        $quote->collectTotals();
        if ($quote->getHasError()) {
            foreach ($quote->getMessages() as $message) {
                $this->_getSesstion()->addError($message->getCode());
            }
        }
        if (!$quote->hasItems()) {
            $this->_getSesstion()->addError(Mage::helper('checkout')->__('You have no items in your shopping cart.'));
            $quote->setHasError(true);
        }
        if (!$quote->validateMinimumAmount()) {
            $error = Mage::getStoreConfig('sales/minimum_order/error_message') ?
                Mage::getStoreConfig('sales/minimum_order/error_message') :
                Mage::helper('checkout')->__('Subtotal must exceed minimum order amount');

            $this->_getSesstion()->addError($error);
            $quote->setHasError(true);
        }
        if (!$this->_allowGuestCheckout()) {
            $this->_getSesstion()->addError(
                Mage::helper('checkout')->__('Sorry, guest checkout is not enabled. Please try again or contact store owner.')
            );
            $quote->setHasError(true);
        }
        if ($quote->getIsMultiShipping()) {
            $this->_getSesstion()->addError(
                Mage::helper('checkout')->__('Invalid checkout type.')
            );
            $quote->setHasError(true);
        }

        // Bypass merge js
        Mage::app()->getStore()->setConfig('dev/js/merge_files', 0);
        Mage::app()->getStore()->setConfig('gtspeed/cssjs/min_js', 0);
        Mage::app()->getStore()->setConfig('gtspeed/cssjs/merge_js', 0);

        // Freeze cart object
        Mage::getSingleton('checkout/session')->setCartWasUpdated(false);

        $this->getOnepage()->initCheckout();
        $this->loadLayout();
        $this->_updateLayout();
        $this->getLayout()->getBlock('head')->setTitle($this->__('Checkout'));
        $this->_initLayoutMessages(array('checkout/session'));
        $this->renderLayout();
    }

    /**
     * Update Onepage layout with custom xml in Settings
     */
    protected function _updateLayout()
    {
        /* @var $helper Mage_Core_Helper_Data */
        $helper = Mage::helper('core');
        $xml = '';

        if ($helper->isModuleEnabled('PostcodeNl_Api')) {
            $xml .= "
<reference name=\"head\">
    <action method=\"addCss\" ifconfig=\"postcodenl_api/config/enabled\"><script>postcodenl/api/css/lookup.css</script></action>
    <action method=\"addJs\" ifconfig=\"postcodenl_api/config/enabled\"><script>postcodenl/api/lookup.js</script></action>
</reference>
<reference name=\"content\">
    <block type=\"postcodenl_api/jsinit\" name=\"postcodenl.jsinit\" template=\"postcodenl/api/jsinit.phtml\" />
</reference>";
        }

        if ($helper->isModuleEnabled('Adyen_Payment')) {
            $xml .= "
<reference name=\"head\">
    <action method=\"addJs\"><script>adyen/payment/cc.js</script></action>
    <action method=\"addJs\"><script>adyen/payment/iban.js</script></action>
    <action method=\"addJs\"><script>adyen/payment/elv.js</script></action>
    <action method=\"addCss\"><stylesheet>css/adyenstyle.css</stylesheet></action>
</reference>
<reference name=\"after_body_start\">
    <block type=\"core/text\" name=\"adyen.diners.validation\" after=\"-\">
        <action method=\"setText\">
            <text><![CDATA[<script type=\"text/javascript\">
                    Validation.creditCartTypes.set('DC', [new RegExp('^3(?:0[0-5]|[68][0-9])[0-9]{11}$'), new RegExp('^[0-9]{3}$'), true]);
                    Validation.creditCartTypes.set('CB', [new RegExp('^4[0-9]{12}([0-9]{3})?$'), new RegExp('^[0-9]{3}$'), true]);
                    Validation.creditCartTypes.set('ELO', [new RegExp(/^((((636368)|(438935)|(504175)|(451416)|(636297)|(506699))\d{0,10})|((5067)|(4576)|(4011))\d{0,12})$/), new RegExp('^[0-9]{3}$'), true]);
                    Validation.creditCartTypes.set('hipercard', [new RegExp(/^(606282\d{10}(\d{3})?)|(3841\d{15})$/), new RegExp('^[0-9]{3}$'), true]);
                    Validation.creditCartTypes.set('unionpay', [new RegExp('^62[0-5]\d{13,16}$'), new RegExp('^[0-9]{3}$'), true]);
                </script>]]>
            </text>
        </action>
    </block>
</reference>";
        }

        if ($helper->isModuleEnabled('TIG_PostNL')) {
            $xml .= "
<reference name=\"head\">
    <action method=\"addCss\"><stylesheet>css/TIG/PostNL/deliveryoptions/default.css</stylesheet></action>
    <action method=\"addCss\"><stylesheet>css/TIG/PostNL/postcodecheck.css</stylesheet></action>
    <action method=\"addCss\"><stylesheet>css/TIG/PostNL/mijnpakket.css</stylesheet></action>
    <action method=\"addItem\"><type>skin_css</type><name>css/TIG/PostNL/deliveryoptions/ie8.css</name><params/><if>lt IE 9</if></action>
    <action method=\"addItem\"><type>skin_js</type><file>js/TIG/PostNL/ajax.js</file></action>
    <action method=\"addItem\"><type>skin_js</type><file>js/TIG/PostNL/postcodecheck.js</file></action>
    <block type=\"postnl_deliveryoptions/theme\" name=\"postnl_deliveryoptions_theme\" template=\"TIG/PostNL/delivery_options/theme.phtml\"/>
</reference>
<reference name=\"checkout.onepage.billing\">
    <block type=\"core/template\" name=\"postnl_billing_postcodecheck\" template=\"TIG/PostNL/address_validation/checkout/onepage/postcode_check.phtml\"/>
</reference>
<reference name=\"checkout.onepage.shipping\">
    <block type=\"core/template\" name=\"postnl_shipping_postcodecheck\" template=\"TIG/PostNL/address_validation/checkout/onepage/postcode_check.phtml\"/>
</reference>
<reference name=\"before_body_end\">
    <block type=\"core/template\" name=\"postnl_validation\" template=\"TIG/PostNL/address_validation/validate.phtml\"/>
    <block type=\"postnl_deliveryoptions/js\" name=\"postnl_deliveryoptions_js\" template=\"TIG/PostNL/delivery_options/js.phtml\"/>
    <block type=\"postnl_mijnpakket/js\" name=\"postnl_mijnpakket_js\" template=\"TIG/PostNL/mijnpakket/js.phtml\"/>
    <block type=\"postnl_mijnpakket/loginButton\" name=\"postnl_mijnpakket_login\" template=\"TIG/PostNL/mijnpakket/onepage/login_button.phtml\"/>
    <block type=\"postnl_deliveryoptions/pickupNotification\" name=\"postnl_billing_pickup_notification\" template=\"TIG/PostNL/delivery_options/onepage/pickup_notification.phtml\"/>
</reference>";
        }

        if ($helper->isModuleEnabled('Vaimo_Klarna')) {
            $xml .= "
<reference name=\"head\">
    <action method=\"addJs\"><script>vaimo/klarna/klarna.js</script></action>
    <action method=\"addCss\"><stylesheet>css/vaimo/klarna/checkout.css</stylesheet></action>
    <block type=\"page/html_head\" name=\"klarna_header\" as=\"klarna_header\" template=\"vaimo/klarna/checkout/header.phtml\" />
</reference>
<reference name=\"content\">
    <block type=\"klarna/checkout_top\" after=\"-\" name=\"klarna_checkout_top\" as=\"klarna_checkout_top\" template=\"vaimo/klarna/checkout/top.phtml\" />
</reference>";
        }

        try {
            $this->getLayout()->getUpdate()->addUpdate($xml);
            $this->generateLayoutXml()->generateLayoutBlocks();
        } catch (Exception $e) {
            Mage::logException($e);
        }

        $customXml = Mage::getStoreConfig('japi/jmango_rest_checkout_settings/custom_xml');
        if ($customXml) {
            try {
                $this->getLayout()->getUpdate()->addUpdate($customXml);
                $this->generateLayoutXml()->generateLayoutBlocks();
            } catch (Exception $e) {
                Mage::logException($e);
            }
        }
    }

    /**
     * Shipping method save action
     */
    public function saveShippingMethodAction()
    {
        if (Mage::helper('core')->isModuleEnabled('Wvzonline_EasyCheckout') && Mage::getStoreConfigFlag('easycheckout/config/is_enabled')) {
            $quote = $this->getOnepage()->getQuote();
            $quote->getShippingAddress()->setShippingMethod($this->getRequest()->getPost('shipping_method', ''));
            $quote->setTotalsCollectedFlag(false);
            $quote->getShippingAddress()->collectTotals();
            $quote->collectTotals();
            $quote->save();

            $result['goto_section'] = 'payment';
            $result['update_section'] = array(
                'name' => 'payment-method',
                'html' => $this->getLayout()->createBlock('easycheckout/payment')
                    ->setTemplate('easycheckout/payment/methods.phtml')
                    ->toHtml()
            );

            return $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
        } else {
            return parent::saveShippingMethodAction();
        }
    }

    /**
     * Save payment ajax action
     *
     * Sets either redirect or a JSON response
     */
    public function savePaymentAction()
    {
        if ($this->_expireAjax()) {
            return;
        }

        try {
            if (!$this->getRequest()->isPost()) {
                $this->_ajaxRedirectResponse();
                return;
            }

            $data = $this->getRequest()->getPost('payment', array());
            $result = $this->getOnepage()->savePayment($data);

            // get section and redirect data
            $redirectUrl = $this->getOnepage()->getQuote()->getPayment()->getCheckoutRedirectUrl();
            if (empty($result['error'])) {
                $result['goto_section'] = 'review';
                $result['update_section'] = array(
                    'name' => 'review',
                    'html' => $this->_getReviewHtml()
                );
            }
            if ($redirectUrl) {
                $result['redirect'] = $redirectUrl;
            }
        } catch (Mage_Payment_Exception $e) {
            if ($e->getFields()) {
                $result['fields'] = $e->getFields();
            }
            $result['error'] = $e->getMessage();
        } catch (Mage_Core_Exception $e) {
            $result['error'] = $e->getMessage();
        } catch (Exception $e) {
            Mage::logException($e);
            $result['error'] = $this->__('Unable to set Payment Method.');
        }

        /* @var $helper Mage_Core_Helper_Data */
        $helper = Mage::helper('core');
        $this->getResponse()->setBody($helper->jsonEncode($result));
    }

    /**
     * Get review order step html
     *
     * @return string
     */
    protected function _getReviewHtml()
    {
        $layout = $this->getLayout();
        $update = $layout->getUpdate();
        // load custom layout
        $update->load('japi_checkout_review');
        $layout->generateXml();
        $layout->generateBlocks();
        $output = $layout->getOutput();
        return $output;
    }

    /**
     * Apply coupon
     */
    public function couponPostAction()
    {
        if ($this->_expireAjax()) {
            return;
        }

        if ($this->getRequest()->getParam('remove') == 1) {
            $couponCode = '';
        } else {
            $couponCode = (string)$this->getRequest()->getParam('coupon_code', '');
        }

        $quote = $this->getOnepage()->getQuote();
        $oldCouponCode = $quote->getCouponCode();
        $output = array();

        if (!strlen($couponCode) && !strlen($oldCouponCode)) {
            return;
        }

        try {
            $codeLength = strlen($couponCode);
            if (version_compare(Mage::getVersion(), '1.8.0', '>=')) {
                $isCodeLengthValid = $codeLength && $codeLength <= Mage_Checkout_Helper_Cart::COUPON_CODE_MAX_LENGTH;
            } else {
                $isCodeLengthValid = $codeLength && $codeLength <= 255;
            }

            $quote->getShippingAddress()->setCollectShippingRates(true);
            $quote->setCouponCode($isCodeLengthValid ? $couponCode : '')
                ->collectTotals()
                ->save();

            if ($codeLength) {
                if ($isCodeLengthValid && $couponCode == $quote->getCouponCode()) {
                    $output['success'] = true;
                    $output['message'] = $this->__('Coupon code "%s" was applied.', Mage::helper('core')->escapeHtml($couponCode));
                    $output['html'] = $this->_getReviewHtml();
                } else {
                    $output['success'] = false;
                    $output['message'] = $this->__('Coupon code "%s" is not valid.', Mage::helper('core')->escapeHtml($couponCode));
                }
            } else {
                $output['success'] = true;
                $output['message'] = $this->__('Coupon code was canceled.');
                $output['html'] = $this->_getReviewHtml();
            }
        } catch (Mage_Core_Exception $e) {
            $output['success'] = false;
            $output['message'] = $e->getMessage();
            Mage::logException($e);
        } catch (Exception $e) {
            $output['success'] = false;
            $output['message'] = $this->__('Cannot apply the coupon code.');
            Mage::logException($e);
        }

        $this->getResponse()->setHeader('Content-Type', 'application/json');
        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($output));
    }

    public function getShippingMethodAction()
    {
        $result = array(
            'update_section' => array(
                'name' => 'shipping-method',
                'html' => $this->_getShippingMethodsHtml(),
            )
        );

        $this->getResponse()->setHeader('Content-Type', 'application/json');
        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
    }

    public function saveOrderAction()
    {
        /* @var $server Jmango360_Japi_Model_Server */
        $server = Mage::getSingleton('japi/server');
        $server->setIsSubmit();

        if ($requiredAgreements = Mage::helper('checkout')->getRequiredAgreementIds()) {
            $postedAgreements = array_keys($this->getRequest()->getPost('agreement', array()));
            $diff = array_diff($requiredAgreements, $postedAgreements);
            if ($diff) {
                $result['success'] = false;
                $result['error'] = true;
                $result['error_messages'] = $this->__('Please agree to all the terms and conditions before placing the order.');
                $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
                return;
            }
        }

        if ($redirectUrl = $this->getRequest()->getPost('redirect')) {
            $result['success'] = true;
            $result['error'] = false;
            $result['redirect'] = $redirectUrl;
            $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
            return;
        }

        parent::saveOrderAction();
    }

    /**
     * Get shipping method step html
     *
     * @return string
     */
    protected function _getShippingMethodsHtml()
    {
        if (Mage::helper('japi')->isModuleEnabled('Netzkollektiv_InStorePickupMulti')) {
            $layout = $this->getLayout();
            $update = $layout->getUpdate();
            $update->load('japi_checkout_onepage_shippingmethod');
            $layout->generateXml();
            $layout->generateBlocks();
            $output = $layout->getOutput();
            return $output;
        } else {
            return parent::_getShippingMethodsHtml();
        }
    }
}
