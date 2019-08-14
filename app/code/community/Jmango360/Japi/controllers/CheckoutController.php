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
        Mage::app()->getStore()->setConfig('dev/js/meanbee_footer_js_enabled', 0);
        Mage::app()->getStore()->setConfig('gtspeed/cssjs/min_js', 0);
        Mage::app()->getStore()->setConfig('gtspeed/cssjs/merge_js', 0);
        Mage::app()->getStore()->setConfig('hsmedia/mediasetting/hsmedia_enabled', 0);
        Mage::app()->getStore()->setConfig('aw_mobile2/general/is_enabled', 0);

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
        /* @var $helper Jmango360_Japi_Helper_Data */
        $helper = Mage::helper('japi');
        $xml = '';

        if ($helper->isModuleEnabled('PostcodeNl_Api')) {
            if (version_compare($helper->getExtensionVersion('PostcodeNl_Api'), '1.1.0', '<')) {
                $xml .= "
<reference name=\"head\">
    <action method=\"addCss\" ifconfig=\"postcodenl/config/enabled\"><script>postcodenl/api/css/lookup.css</script></action>
    <action method=\"addJs\" ifconfig=\"postcodenl/config/enabled\"><script>postcodenl/api/lookup.js</script></action>
</reference>";
            } else {
                $xml .= "
<reference name=\"head\">
    <action method=\"addCss\" ifconfig=\"postcodenl_api/config/enabled\"><script>postcodenl/api/css/lookup.css</script></action>
    <action method=\"addJs\" ifconfig=\"postcodenl_api/config/enabled\"><script>postcodenl/api/lookup.js</script></action>
</reference>";
            }
            $xml .= "
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
                </script>]]></text>
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

        if ($helper->isModuleEnabled('AW_Deliverydate')) {
            $xml .= "
<reference name=\"head\">
    <action method=\"addJs\"><script>varien/product.js</script></action>
    <action method=\"addCss\"><stylesheet>aw_deliverydate/css/main.css</stylesheet></action>
    <action method=\"addItem\"><type>js_css</type><name>calendar/calendar-win2k-1.css</name><params/></action>
    <action method=\"addItem\"><type>js</type><name>calendar/calendar.js</name></action>
    <action method=\"addItem\"><type>js</type><name>calendar/calendar-setup.js</name></action>
    <action method=\"addJs\"><script>jquery/jquery.1.9.1.min.js</script></action>
    <action method=\"addJs\"><script>jquery/jquery.noConflict.js</script></action>
    <action method=\"addJs\"><script>pickadate/picker.js</script></action>
    <action method=\"addJs\"><script>pickadate/picker.date.js</script></action>
    <action method=\"addItem\"><type>js_css</type><stylesheet>pickadate/theme/default.css</stylesheet></action>
    <action method=\"addItem\"><type>js_css</type><stylesheet>pickadate/theme/default.date.css</stylesheet></action>
    <block type=\"core/html_calendar\" name=\"html_calendar\" as=\"html_calendar\" template=\"page/js/calendar.phtml\"/>
</reference>";
        }

        if ($helper->isModuleEnabled('GoMage_DeliveryDate')) {
            $xml .= "
<reference name=\"head\">			
    <action method=\"addItem\"><type>js</type><name>gomage/lc-calendar.js</name></action>
    <block type=\"core/html_calendar\" name=\"gomage.deliverydate.calendar\" template=\"gomage/deliverydate/js/calendar.phtml\"/>
</reference>		 
<reference name=\"checkout.onepage.shipping_method.advanced\">
    <block type=\"gomage_deliverydate/form\" name=\"checkout.onepage.shipping_method.additional.delivery_date\" template=\"gomage/deliverydate/form.phtml\" />
</reference>";
        }

        if ($helper->isModuleEnabled('SendCloud_Integration')) {
            $xml .= "
<reference name=\"head\">
    <action method=\"addJs\"><script>sendcloud/onepage.js</script></action>
    <block type=\"page/html\" name=\"sendcloud_jsvalues\" template=\"sendcloud/servicepointpicker.phtml\" />
</reference>";
        }

        if ($helper->isModuleEnabled('Symfony_Postcode')) {
            $xml .= "
<reference name=\"head\">
    <action method=\"addItem\" ifconfig=\"symfony_postcode/settings/enabled\"><type>js</type><file>symfony/postcode.js</file></action>
    <action method=\"addItem\" ifconfig=\"symfony_postcode/settings/enabled\"><type>skin_css</type><file>css/symfony/postcode.css</file></action>
</reference>";
        }

        if ($helper->isModuleEnabled('TIG_Buckaroo3Extended')) {
            $xml .= "
<reference name=\"head\">
    <block type=\"core/template\" name=\"buckaroo_jquery\" template=\"buckaroo3extended/jquery.phtml\"/>
    <action method=\"addItem\"><type>skin_css</type><name>japi/css/TIG/Buckaroo3Extended/styles_opc.css</name></action>
    <action method=\"addItem\"><type>skin_js</type><name>js/TIG/Buckaroo3Extended/paymentGuaranteeObserver.js</name></action>
    <action method=\"addItem\"><type>skin_js</type><name>js/TIG/Buckaroo3Extended/afterpayObserver.js</name></action>
</reference>
<reference name=\"before_body_end\">
        <block type=\"core/template\" name=\"buckaroo_save_sata_js\" template=\"buckaroo3extended/saveData.phtml\"/>
</reference>";
        }

        if ($helper->isModuleEnabled('Fooman_GoogleAnalyticsPlus')) {
            $xml .= "
<reference name=\"head\">
    <block type=\"core/text_list\" name=\"before_head_end\" as=\"before_head_end\"/>
</reference>
<reference name=\"before_head_end\">
    <block type=\"googleanalyticsplus/universal\" name=\"googleanalyticsplus_universal\" as=\"googleanalyticsplus_universal\"/>
</reference>
<reference name=\"after_body_start\">
    <block type=\"googleanalyticsplus/tagManager\" name=\"googleanalyticsplus_tagmanager\" as=\"googleanalyticsplus_tagmanager\"/>
</reference>
<reference name=\"before_body_end\">
    <block type=\"googleanalyticsplus/remarketing\" name=\"googleanalyticsplus_remarketing\" as=\"googleanalyticsplus_remarketing\"/>
</reference>";
        }

        if ($helper->isModuleEnabled('Sevenlike_Fatturazione')) {
            $xml .= "
<reference name=\"head\">
    <action method=\"addItem\"><type>skin_js</type><name>js/fatturazione.js</name></action>
    <action method=\"addItem\"><type>skin_js</type><name>onestepcheckout/js/autocomplete.js</name></action>
    <action method=\"addCss\"><stylesheet>onestepcheckout/autocomplete.css</stylesheet></action>
</reference>";
        }

        if ($helper->isModuleEnabled('Bitbull_BancaSellaPro')) {
            $xml .= "
<reference name=\"head\">
    <block type=\"bitbull_bancasellapro/utility_text\" name=\"gestpay.iframe.external\"/>
    <action method=\"addJs\"><script>prototype/window.js</script></action>
    <action method=\"addItem\"><type>js_css</type><name>prototype/windows/themes/default.css</name></action>
    <action method=\"addCss\"><name>lib/prototype/windows/themes/magento.css</name></action>
    <action method=\"addJs\"><name>bancasellapro/gestpayform.js</name></action>
</reference>";
        }

        if ($helper->isModuleEnabled('Kega_Checkout')) {
            $xml .= "
<reference name=\"head\">
    <action method=\"addItem\">
        <type>skin_js</type>
        <name>js/kega_theme/vendor/magnific.popup.min.js</name>
    </action>
    <action method=\"addItem\">
        <type>skin_js</type>
        <name>js/kega_checkout/main.js</name>
    </action>
    <action method=\"addItem\">
        <type>skin_js</type>
        <name>js/kega_form/main.js</name>
    </action>
    <action method=\"addItem\">
        <type>skin_js</type>
        <name>js/terstal_form/main.js</name>
    </action>
    <action method=\"addItem\">
        <type>skin_js</type>
        <name>js/kega_form/address-search.js</name>
    </action>
</reference>
<reference name=\"checkout.onepage.billing\">
    <action method=\"setTemplate\">
        <template>kega_checkout/onepage/billing.phtml</template>
    </action>
    <block type=\"checkout/onepage\" name=\"newsletter_subscription\"
        template=\"kega_checkout/onepage/newsletter_subscription.phtml\" />
    <block type=\"core/text_list\" name=\"checkout_billing_before_form\"/>
    <block type=\"core/text_list\" name=\"checkout_after_before_form\"/>
</reference>
<reference name=\"checkout.onepage.shipping_method\">
    <remove name=\"checkout.onepage.shipping_method.additional\"/>
</reference>
<reference name=\"checkout.onepage.shipping_method.available\">
    <action method=\"setTemplate\">
        <template>kega_checkout/onepage/shipping_method/available.phtml</template>
    </action>
</reference>
<reference name=\"checkout.onepage.payment\">
    <action method=\"setTemplate\">
        <template>japi/kega_checkout/onepage/payment.phtml</template>
    </action>
</reference>
<reference name=\"checkout.onepage.payment.additional\">
    <block type=\"checkout/agreements\" name=\"checkout.onepage.agreements\" as=\"additional\"
        template=\"japi/checkout/onepage/agreements.phtml\"/>
</reference>
<reference name=\"before_body_end\">
    <block type=\"kega_form/attributes\" name=\"kega.form.attributes\" template=\"kega_form/attributes.phtml\"/>
    <block type=\"kega_form/address_search\" name=\"address_search\" template=\"kega_form/address-search.phtml\"/>
</reference>";
        }

        if ($helper->isModuleEnabled('Kega_StorePickup')) {
            $xml .= "
<reference name=\"head\">
<block type=\"core/template\" name=\"google.maps.js\" template=\"kega_store/js/maps.phtml\"/>
<action method=\"addItem\">
    <type>skin_js</type>
    <name>js/kega_storepickup/main.js</name>
</action>
</reference>
<reference name=\"checkout.onepage.shipping_method.available\">
    <block type=\"storepickup/checkout_onepage_shipping_storepickup_storelist\" name=\"kega_storepickup_delivery\"
           template=\"kega_storepickup/form.phtml\"/>
</reference>";
        }

        if ($helper->isModuleEnabled('Chronopost_Chronorelais')) {
            $xml .= "
<reference name=\"head\">
    <action method=\"addItem\"><type>skin_css</type><name>chronorelais/chronorelais.css</name></action>
    <action method=\"addItem\"><type>skin_js</type><name>chronorelais/carousel-min.js</name></action>
</reference>
<reference name=\"content\">
    <block type=\"core/template\" template=\"chronorelais/checkout/onepage/shipping_method_complement.phtml\" />
</reference>";
        }

        if (Mage::getEdition() == Mage::EDITION_ENTERPRISE) {
            $xml .= "
<reference name=\"content\">
    <block type=\"core/template\" name=\"pbridge.js\" template=\"pbridge/checkout/payment/js.phtml\" />
</reference>";
        }

        if ($helper->isModuleEnabled('Magestore_RewardPoints')) {
            $xml .= "
<reference name=\"head\">
    <action method=\"addJs\"><file>magestore/rewardpoints.js</file></action>
</reference>";
        }

        if ($helper->isModuleEnabled('Iways_PayPalPlus')) {
            $xml .= "
<reference name=\"head\">
    <block type=\"core/text\" name=\"ppplus\" ifconfig=\"payment/iways_paypalplus_payment/active\">
        <action method=\"setText\">
            <text><![CDATA[<script src=\"//www.paypalobjects.com/webstatic/ppplus/ppplus.min.js\" type=\"text/javascript\"></script>]]></text>
        </action>
    </block>
    <action method=\"addCss\" ifconfig=\"payment/iways_paypalplus_payment/active\">
        <stylesheet>css/iways-paypalplus.css</stylesheet>
    </action>
</reference>";
        }

        if ($helper->isModuleEnabled('TIG_MyParcel2014')) {
            $xml .= "
<reference name=\"head\">
    <action method=\"addItem\">
        <type>skin_js</type>
        <file>js/TIG/MyParcel2014/jquery-myparcel.min.js</file>
    </action>
    <action method=\"addItem\">
        <type>skin_js</type>
        <file>js/TIG/MyParcel2014/checkout.js</file>
    </action>
</reference>
<reference name=\"checkout.onepage.shipping_method.available\">
    <action method=\"setTemplate\">
        <template>TIG/MyParcel2014/checkout/onepage/shipping_method/available.phtml</template>
    </action>
</reference>";
        }

        if ($helper->isModuleEnabled('LaPoste_SoColissimoSimplicite')) {
            $xml .= "
<reference name=\"head\">
    <action method=\"addItem\"><type>skin_js</type><name>js/socolissimosimplicite/shipping_method.js</name></action>
</reference>
<reference name=\"content\">
    <block type=\"japi/socolissimosimplicite_iframe\" name=\"iframe.socolissimosimplicite\" template=\"socolissimosimplicite/iframe.phtml\" after=\"checkout.onepage\" />
    <block type=\"core/template\" name=\"shippingmethod.socolissimosimplicite\" template=\"socolissimosimplicite/onepage/shipping_method/socolissimosimplicite.phtml\" after=\"iframe.socolissimosimplicite\" />
</reference>";
        }

        if ($helper->isModuleEnabled('Borgione_General')) {
            $xml .= "
<reference name=\"checkout.onepage.billing\">
    <action method=\"setTemplate\">
        <template>persistent/checkout/onepage/billing.phtml</template>
    </action>
</reference>
<reference name=\"checkout.onepage.shipping\">
    <action method=\"setTemplate\">
        <template>checkout/onepage/shipping.phtml</template>
    </action>
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
     * Save checkout billing address
     */
    public function saveBillingAction()
    {
        Mage::app()->getStore()->setConfig('aw_mobile2/general/is_enabled', 0);
        Mage::getSingleton('core/session')->setData('is_address_update', true);
        return parent::saveBillingAction();
    }

    /**
     * Shipping address save action
     */
    public function saveShippingAction()
    {
        Mage::app()->getStore()->setConfig('aw_mobile2/general/is_enabled', 0);
        return parent::saveShippingAction();
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
        } elseif (Mage::helper('core')->isModuleEnabled('InfoDesires_CashOnDelivery') && Mage::getStoreConfigFlag('payment/cashondelivery/active')) {
            if ($this->_expireAjax()) {
                return false;
            }
            if ($this->getRequest()->isPost()) {
                $data = $this->getRequest()->getPost('shipping_method', '');
                $result = $this->getOnepage()->saveShippingMethod($data);
                // $result will contain error data if shipping method is empty
                if (!$result) {
                    Mage::dispatchEvent(
                        'checkout_controller_onepage_save_shipping_method',
                        array(
                            'request' => $this->getRequest(),
                            'quote' => $this->getOnepage()->getQuote()
                        )
                    );
                    $this->getOnepage()->getQuote()->collectTotals();
                    $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));

                    $result['goto_section'] = 'payment';
                    $result['update_section'] = array(
                        'name' => 'payment-method',
                        'html' => $this->_getPaymentMethodsHtml()
                    );
                }
                $this->getOnepage()->getQuote()->collectTotals()->save();
                $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
            }
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
            if (Mage::getEdition() == 'Community' && version_compare(Mage::getVersion(), '1.8.0', '>=')) {
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
        $messages = array();
        $quote = $this->getOnepage()->getQuote();
        if (!$quote->getItemsCount()) {
            $messages[] = Mage::helper('japi')->__('Cart is empty.');
        }
        if (count($messages)) {
            $result['success'] = false;
            $result['error'] = true;
            $result['error_messages'] = implode("\n", $messages);
            return $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
        }

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
                return $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
            }
        }

        if ($subscribeNewsletter = $this->getRequest()->getPost('subscribe_newsletter')) {
            if (Mage::helper('core')->isModuleEnabled('Mage_Newsletter')) {
                /* @var $subscriberModel Mage_Newsletter_Model_Subscriber */
                $subscriberModel = Mage::getModel('newsletter/subscriber');
                $subscriberModel->loadByEmail($quote->getCustomerEmail());
                if (!$subscriberModel->isSubscribed()) {
                    $subscribeobj = $subscriberModel->subscribe($quote->getCustomerEmail());
                    if (is_object($subscribeobj)) {
                        $subscribeobj->save();
                    }
                }
            }
        }

        if ($redirectUrl = $this->getRequest()->getPost('redirect')) {
            $result['success'] = true;
            $result['error'] = false;
            $result['redirect'] = $redirectUrl;
            return $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
        }

        /**
         * Flag as JMango360 order
         */
        $this->getOnepage()->getQuote()->setData('japi', 1);

        /**
         * Clear some flags
         */
        Mage::getSingleton('core/session')->unsetData('is_address_update');

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

    /**
     * Get payment method step html
     *
     * @return string
     */
    protected function _getPaymentMethodsHtml()
    {
        if (Mage::helper('core')->isModuleEnabled('InfoDesires_CashOnDelivery') && Mage::getStoreConfigFlag('payment/cashondelivery/active')) {
            $layout = $this->getLayout();
            $update = $layout->getUpdate();
            $update->load('japi_checkout_onepage_paymentmethod');
            $layout->generateXml();
            $layout->generateBlocks();
            $output = $layout->getOutput();
            return $output;
        } else {
            return parent::_getPaymentMethodsHtml();
        }
    }
}
