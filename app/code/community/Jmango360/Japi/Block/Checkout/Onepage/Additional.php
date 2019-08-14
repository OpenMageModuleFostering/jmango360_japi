<?php
/**
 * Copyright 2017 JMango360
 */

/**
 * Class Jmango360_Japi_Block_Checkout_Onepage_Additional
 */
class Jmango360_Japi_Block_Checkout_Onepage_Additional extends Mage_Core_Block_Template
{
    public function isShowNewsletter()
    {
        $helper = Mage::helper('core');
        if (!$helper->isModuleEnabled('Mage_Newsletter')) {
            return false;
        }
        if ($helper->isModuleEnabled('Idev_OneStepCheckout')) {
            /* @var $oscHelper Idev_OneStepCheckout_Helper_Checkout */
            $oscHelper = Mage::helper('onestepcheckout/checkout');
            if ($oscHelper && method_exists($oscHelper, 'loadConfig')) {
                $oscSettings = $oscHelper->loadConfig();
                $customerEmail = $this->getCustomerEmail();
                return isset($oscSettings['enable_newsletter']) && $oscSettings['enable_newsletter'] && !$this->isSubscribed($customerEmail);
            }
        }

        return false;
    }

    public function isNewsletterChecked()
    {
        if (Mage::helper('core')->isModuleEnabled('Idev_OneStepCheckout')) {
            /* @var $oscHelper Idev_OneStepCheckout_Helper_Checkout */
            $oscHelper = Mage::helper('onestepcheckout/checkout');
            if ($oscHelper && method_exists($oscHelper, 'loadConfig')) {
                $oscSettings = $oscHelper->loadConfig();
                return !empty($oscSettings['newsletter_default_checked']);
            }
        }

        return false;
    }

    public function getCustomerEmail()
    {
        /* @var $checkoutSession Mage_Checkout_Model_Session */
        $checkoutSession = Mage::getSingleton('checkout/session');
        $quote = $checkoutSession->getQuote();
        $customerEmail = $quote->getCustomerEmail();

        return $customerEmail;
    }

    /**
     * Check if e-mail address is subscribed to newsletter
     *
     * @param $email string
     * @return boolean
     */
    protected function isSubscribed($email = null)
    {
        $isSubscribed = false;

        if (!empty($email)) {
            try {
                /* @var $result Mage_Newsletter_Model_Subscriber */
                $result = Mage::getModel('newsletter/subscriber')->loadByEmail($email);
                if (is_object($result) && $result->getSubscriberStatus() == 1) {
                    $isSubscribed = true;
                }
            } catch (Exception $e) {
            }
        }

        return $isSubscribed;
    }
}