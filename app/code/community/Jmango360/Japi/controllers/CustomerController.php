<?php
require_once 'Mage/Customer/controllers/AccountController.php';

class Jmango360_Japi_CustomerController extends Mage_Customer_AccountController
{
    public function preDispatch()
    {
        call_user_func(array(get_parent_class(get_parent_class($this)), 'preDispatch'));
    }

    public function registerAction()
    {
        $this->loadLayout();
        $this->_updateLayoutRegister();
        $this->getLayout()->getBlock('head')->setTitle($this->__('Create New Customer Account'));
        $session = $this->_getSession();
        $session->setBeforeAuthUrl($this->_getUrl('*/*/register', array('_secure' => true)));
        if ($session->getIsSubmit()) {
            $session->setIsSubmit(false);
            $this->getResponse()->setHeader('JM-Account-Id', $session->getCustomerId());
            $this->getResponse()->setHeader('JM-Account-Email', $session->getCustomerEmail());
            $this->getResponse()->setHeader('JM-Confirmation-Required', $session->getIsConfirmationRequired());
            $this->getResponse()->setHeader('JM-Session-Id', $session->getSessionId());
        }
        $this->_initLayoutMessages(array('core/session', 'customer/session'));
        $this->renderLayout();
    }

    public function createPostAction()
    {
        if (version_compare(Mage::getVersion(), '1.8.0', '<')) {
            $session = $this->_getSession();
            if ($session->isLoggedIn()) {
                $this->_redirect('*/*/');
                return;
            }
            $session->setEscapeMessages(true); // prevent XSS injection in user input
            if ($this->getRequest()->isPost()) {
                $errors = array();

                if (!$customer = Mage::registry('current_customer')) {
                    $customer = Mage::getModel('customer/customer')->setId(null);
                }

                /* @var $customerForm Mage_Customer_Model_Form */
                $customerForm = Mage::getModel('customer/form');
                $customerForm->setFormCode('customer_account_create')
                    ->setEntity($customer);

                $customerData = $customerForm->extractData($this->getRequest());

                if ($this->getRequest()->getParam('is_subscribed', false)) {
                    $customer->setIsSubscribed(1);
                }

                /**
                 * Initialize customer group id
                 */
                $customer->getGroupId();

                if ($this->getRequest()->getPost('create_address')) {
                    /* @var $address Mage_Customer_Model_Address */
                    $address = Mage::getModel('customer/address');
                    /* @var $addressForm Mage_Customer_Model_Form */
                    $addressForm = Mage::getModel('customer/form');
                    $addressForm->setFormCode('customer_register_address')
                        ->setEntity($address);

                    $addressData = $addressForm->extractData($this->getRequest(), 'address', false);
                    $addressErrors = $addressForm->validateData($addressData);
                    if ($addressErrors === true) {
                        $address->setId(null)
                            ->setIsDefaultBilling($this->getRequest()->getParam('default_billing', false))
                            ->setIsDefaultShipping($this->getRequest()->getParam('default_shipping', false));
                        $addressForm->compactData($addressData);
                        $customer->addAddress($address);

                        $addressErrors = $address->validate();
                        if (is_array($addressErrors)) {
                            $errors = array_merge($errors, $addressErrors);
                        }
                    } else {
                        $errors = array_merge($errors, $addressErrors);
                    }
                }

                try {
                    $customerErrors = $customerForm->validateData($customerData);
                    if ($customerErrors !== true) {
                        $errors = array_merge($customerErrors, $errors);
                    } else {
                        $customerForm->compactData($customerData);
                        $customer->setPassword($this->getRequest()->getPost('password'));
                        $customer->setConfirmation($this->getRequest()->getPost('confirmation'));
                        $customerErrors = $customer->validate();
                        if (is_array($customerErrors)) {
                            $errors = array_merge($customerErrors, $errors);
                        }
                    }

                    $validationResult = count($errors) == 0;

                    if (true === $validationResult) {
                        $customer->save();

                        Mage::dispatchEvent('customer_register_success',
                            array('account_controller' => $this, 'customer' => $customer)
                        );

                        if ($customer->isConfirmationRequired()) {
                            $customer->sendNewAccountEmail(
                                'confirmation',
                                $session->getBeforeAuthUrl(),
                                Mage::app()->getStore()->getId()
                            );
                            $session->addSuccess($this->__('Account confirmation is required. Please, check your email for the confirmation link. To resend the confirmation email please <a href="%s">click here</a>.', Mage::helper('customer')->getEmailConfirmationUrl($customer->getEmail())));
                            $this->_redirectSuccess(Mage::getUrl('*/*/index', array('_secure' => true)));
                            return;
                        } else {
                            //$session->setCustomerAsLoggedIn($customer);
                            $url = $this->_welcomeCustomer($customer);
                            $this->_redirectSuccess($url);
                            return;
                        }
                    } else {
                        $session->setCustomerFormData($this->getRequest()->getPost());
                        if (is_array($errors)) {
                            foreach ($errors as $errorMessage) {
                                $session->addError($errorMessage);
                            }
                        } else {
                            $session->addError($this->__('Invalid customer data'));
                        }
                    }
                } catch (Mage_Core_Exception $e) {
                    $session->setCustomerFormData($this->getRequest()->getPost());
                    if ($e->getCode() === Mage_Customer_Model_Customer::EXCEPTION_EMAIL_EXISTS) {
                        $url = Mage::getUrl('customer/account/forgotpassword');
                        $message = $this->__('There is already an account with this email address. If you are sure that it is your email address, <a href="%s">click here</a> to get your password and access your account.', $url);
                        $session->setEscapeMessages(false);
                    } else {
                        $message = $e->getMessage();
                    }
                    $session->addError($message);
                } catch (Exception $e) {
                    $session->setCustomerFormData($this->getRequest()->getPost())
                        ->addException($e, $this->__('Cannot save the customer.'));
                }
            }

            $this->_redirectError(Mage::getUrl('*/*/create', array('_secure' => true)));
        } else {
            parent::createPostAction();
        }
    }

    protected function _welcomeCustomer(Mage_Customer_Model_Customer $customer, $isJustConfirmed = false)
    {
        $url = parent::_welcomeCustomer($customer, $isJustConfirmed);
        $this->_getSession()->addSuccess(
            $this->__('Please close this form and login with your new account.')
        );
        return $url;
    }

    protected function _getCustomer()
    {
        $customer = $this->_getFromRegistry('current_customer');
        if (!$customer) {
            $customer = $this->_getModel('customer/customer')->setId(null);
        }
        if ($this->getRequest()->getParam('is_subscribed', false)) {
            $customer->setIsSubscribed(1);
        }
        /**
         * Initialize customer group id
         */
        $customer->getGroupId();

        // Flag as JMango360 user
        $customer->setData('japi', 1);

        return $customer;
    }

    protected function _successProcessRegistration(Mage_Customer_Model_Customer $customer)
    {
        $session = $this->_getSession();
        if ($customer->isConfirmationRequired()) {
            /** @var $app Mage_Core_Model_App */
            $app = $this->_getApp();
            /** @var $store  Mage_Core_Model_Store */
            $store = $app->getStore();
            $customer->sendNewAccountEmail(
                'confirmation',
                $session->getBeforeAuthUrl(),
                $store->getId()
            );
            $customerHelper = $this->_getHelper('customer');
            $session->addSuccess($this->__('Account confirmation is required. Please, check your email for the confirmation link. To resend the confirmation email please <a href="%s">click here</a>.',
                $customerHelper->getEmailConfirmationUrl($customer->getEmail())));
            $url = $this->_getUrl('*/*/index', array('_secure' => true));
        } else {
            //$session->setCustomerAsLoggedIn($customer);
            $url = $this->_welcomeCustomer($customer);
        }
        $this->_redirectSuccess($url);
        return $this;
    }

    public function editAction()
    {
        $this->loadLayout();
        $this->getLayout()->getBlock('head')->setTitle($this->__('Account Information'));
        $session = $this->_getSession();
        if ($session->getIsSubmit()) {
            $session->setIsSubmit(false);
            $this->getResponse()->setHeader('JM-Account-Id', $session->getCustomerId());
            $this->getResponse()->setHeader('JM-Account-Email', $session->getCustomer()->getEmail());
        }
        if (!$session->isLoggedIn()) {
            $session->addError(Mage::helper('japi')->__('Customer not logged in'));
            $this->getResponse()->setHeader('HTTP/1.1', '401 Unauthorized', true);
        }
        if (!Mage::registry('current_customer')) {
            Mage::register('current_customer', $session->getCustomer());
        }
        $this->_initLayoutMessages(array('core/session', 'customer/session'));
        $this->renderLayout();
    }

    /**
     * @return Mage_Customer_Model_Session
     */
    protected function _getSession()
    {
        return Mage::getSingleton('customer/session');
    }

    /**
     * @param $path
     * @param array $arguments
     * @return false|Mage_Core_Model_Abstract
     */
    public function _getModel($path, $arguments = array())
    {
        return Mage::getModel($path, $arguments);
    }

    /**
     * @param string $path
     * @return Mage_Core_Helper_Abstract
     */
    protected function _getHelper($path)
    {
        return Mage::helper($path);
    }

    public function editPostAction()
    {
        if (!$this->_validateFormKey()) {
            return $this->_redirect('*/*/edit', array('_secure' => true));
        }

        if ($this->getRequest()->isPost()) {
            $this->_getSession()->setIsSubmit(true);

            /** @var $customer Mage_Customer_Model_Customer */
            $customer = $this->_getSession()->getCustomer();

            /** @var $customerForm Mage_Customer_Model_Form */
            $customerForm = $this->_getModel('customer/form');
            $customerForm->setFormCode('customer_account_edit')
                ->setEntity($customer);

            $customerData = $customerForm->extractData($this->getRequest());

            $errors = array();
            $customerErrors = $customerForm->validateData($customerData);
            if ($customerErrors !== true) {
                $errors = array_merge($customerErrors, $errors);
            } else {
                $customerForm->compactData($customerData);
                $errors = array();

                // If password change was requested then add it to common validation scheme
                if ($this->getRequest()->getParam('change_password')) {
                    $currPass = $this->getRequest()->getPost('current_password');
                    $newPass = $this->getRequest()->getPost('password');
                    $confPass = $this->getRequest()->getPost('confirmation');

                    $oldPass = $this->_getSession()->getCustomer()->getPasswordHash();
                    if ($this->_getHelper('core/string')->strpos($oldPass, ':')) {
                        list($_salt, $salt) = explode(':', $oldPass);
                    } else {
                        $salt = false;
                    }

                    if ($customer->hashPassword($currPass, $salt) == $oldPass) {
                        if (strlen($newPass)) {
                            /**
                             * Set entered password and its confirmation - they
                             * will be validated later to match each other and be of right length
                             */
                            $customer->setPassword($newPass);
                            $customer->setPasswordConfirmation($confPass);
                            $customer->setConfirmation($confPass);
                        } else {
                            $errors[] = $this->__('New password field cannot be empty.');
                        }
                    } else {
                        $errors[] = $this->__('Invalid current password');
                    }
                }

                // Validate account and compose list of errors if any
                $customerErrors = $customer->validate();
                if (is_array($customerErrors)) {
                    $errors = array_merge($errors, $customerErrors);
                }
            }

            if (!empty($errors)) {
                $this->_getSession()->setCustomerFormData($this->getRequest()->getPost());
                foreach ($errors as $message) {
                    $this->_getSession()->addError($message);
                }
                $this->_redirect('*/*/edit', array('_secure' => true));
                return $this;
            }

            try {
                $customer->setConfirmation(null);
                $customer->save();
                $this->_getSession()->setCustomer($customer)
                    ->addSuccess($this->__('The account information has been saved.'));

                if ($this->_getSession()->getCheckoutReferer()) {
                    $this->_getSession()->setCheckoutReferer(false);
                    $this->_getSession()->setIsSubmit(false);
                    return $this->_redirect('japi/checkout/onepage', array('_secure' => true));
                } else {
                    return $this->_redirect('*/*/edit', array('_secure' => true));
                }
            } catch (Mage_Core_Exception $e) {
                $this->_getSession()->setCustomerFormData($this->getRequest()->getPost())
                    ->addError($e->getMessage());
            } catch (Exception $e) {
                $this->_getSession()->setCustomerFormData($this->getRequest()->getPost())
                    ->addException($e, $this->__('Cannot save the customer.'));
            }
        }

        $this->_redirect('*/*/edit', array('_secure' => true));
    }

    public function addressAction()
    {
        $this->loadLayout();
        $this->_updateLayout();
        $session = $this->_getSession();
        $customer = $session->getCustomer();
        $addressId = $this->getRequest()->getParam('id');
        $address = $customer->getAddressById($addressId);
        if ($address->getId()) {
            $this->getLayout()->getBlock('head')->setTitle($this->__('Edit Address'));
        } else {
            $this->getLayout()->getBlock('head')->setTitle($this->__('Add New Address'));
        }
        if ($session->getIsSubmit()) {
            $session->setIsSubmit(false);
            $this->getResponse()->setHeader('JM-Account-Id', $session->getCustomerId());
            $this->getResponse()->setHeader('JM-Account-Email', $session->getCustomer()->getEmail());
            $this->getResponse()->setHeader('JM-Address-Id', $this->getRequest()->getParam('id'));
        }
        if (!$session->isLoggedIn()) {
            $session->addError(Mage::helper('japi')->__('Customer not logged in'));
            $this->getResponse()->setHeader('HTTP/1.1', '401 Unauthorized', true);
        }
        if ($this->getRequest()->getParam('is_checkout')) {
            /* @var $httpHelper Mage_Core_Helper_Http */
            $httpHelper = Mage::helper('core/http');
            $session->setRefererUrl($httpHelper->getHttpReferer());
        }
        $this->_initLayoutMessages(array('core/session', 'customer/session'));
        $this->renderLayout();
    }

    public function addressPostAction()
    {
        if (!$this->_validateFormKey()) {
            return $this->_redirect('*/*/', array('_secure' => true));
        }
        // Save data
        if ($this->getRequest()->isPost()) {
            $this->_getSession()->setIsSubmit(true);

            $customer = $this->_getSession()->getCustomer();
            /* @var $address Mage_Customer_Model_Address */
            $address = Mage::getModel('customer/address');
            $addressId = $this->getRequest()->getParam('id');
            if ($addressId) {
                $existsAddress = $customer->getAddressById($addressId);
                if ($existsAddress->getId() && $existsAddress->getCustomerId() == $customer->getId()) {
                    $address->setId($existsAddress->getId());
                }
            }

            $errors = array();

            /* @var $addressForm Mage_Customer_Model_Form */
            $addressForm = Mage::getModel('customer/form');
            $addressForm->setFormCode('customer_address_edit')
                ->setEntity($address);
            $addressData = $addressForm->extractData($this->getRequest());
            $addressErrors = $addressForm->validateData($addressData);
            if ($addressErrors !== true) {
                $errors = $addressErrors;
            }

            try {
                $addressForm->compactData($addressData);
                $address->setCustomerId($customer->getId())
                    ->setIsDefaultBilling($this->getRequest()->getParam('default_billing', false))
                    ->setIsDefaultShipping($this->getRequest()->getParam('default_shipping', false));

                $addressErrors = $address->validate();
                if ($addressErrors !== true) {
                    $errors = array_merge($errors, $addressErrors);
                }

                if (count($errors) === 0) {
                    $address->save();
                    if ($this->getRequest()->getParam('is_checkout')) {
                        $redirectUrl = $this->_getSession()->getRefererUrl();
                        if ($redirectUrl) {
                            if (strpos($redirectUrl, 'japi/checkout/onepage') !== false) {
                                $type = $this->getRequest()->getParam('type');
                                if ($type == 'billing') {

                                } elseif ($type == 'shipping') {
                                    Mage::getSingleton('core/session')->setData('is_shipping_address_update', true);
                                }
                            }
                            $this->_getSession()->unsetData('referer_url');
                            return $this->_redirectUrl($redirectUrl);
                        } else {
                            return $this->_redirect('japi/checkout/onepage', array('_secure' => true));
                        }
                    } else {
                        $this->_getSession()->addSuccess($this->__('The address has been saved.'));
                        return $this->_redirect('*/*/address', array('_secure' => true, 'id' => $address->getId()));
                    }
                } else {
                    $this->_getSession()->setAddressFormData($this->getRequest()->getPost());
                    foreach ($errors as $errorMessage) {
                        $this->_getSession()->addError($errorMessage);
                    }
                }
            } catch (Mage_Core_Exception $e) {
                $this->_getSession()->setAddressFormData($this->getRequest()->getPost())
                    ->addException($e, $e->getMessage());
            } catch (Exception $e) {
                $this->_getSession()->setAddressFormData($this->getRequest()->getPost())
                    ->addException($e, $this->__('Cannot save address.'));
            }
        }

        return $this->_redirect('*/*/address', array('_secure' => true, '_current' => true));
    }

    protected function _updateLayoutRegister()
    {
        $xml = '';

        if (Mage::helper('core')->isModuleEnabled('GGMGastro_CustomCheckoutFields')) {
            $xml .= "
<reference name=\"head\">
    <action method=\"addJs\"><script>GGMGastro/CustomCheckoutFields/AdditionalFields.js</script></action>
</reference>
<reference name=\"customer_form_register\">
    <action method=\"setTemplate\"><template>persistent/customer/form/register.phtml</template></action>
</reference>";
        }

        try {
            $this->getLayout()->getUpdate()->addUpdate($xml);
            $this->generateLayoutXml()->generateLayoutBlocks();
        } catch (Exception $e) {
            Mage::logException($e);
        }
    }

    protected function _updateLayout()
    {
        $xml = '';

        if (Mage::helper('core')->isModuleEnabled('PostcodeNl_Api')) {
            $xml .= "
<reference name=\"head\">
    <action method=\"addCss\" ifconfig=\"postcodenl_api/config/enabled\"><script>postcodenl/api/css/lookup.css</script></action>
    <action method=\"addJs\" ifconfig=\"postcodenl_api/config/enabled\"><script>postcodenl/api/lookup.js</script></action>
</reference>
<reference name=\"content\">
    <block type=\"postcodenl_api/jsinit\" name=\"postcodenl.jsinit\" template=\"postcodenl/api/jsinit.phtml\" />
</reference>";
        }

        if (Mage::helper('core')->isModuleEnabled('TIG_PostNL')) {
            $xml .= "
<reference name=\"head\">
    <action method=\"addItem\"><type>skin_js</type><file>js/TIG/PostNL/ajax.js</file></action>
    <action method=\"addItem\"><type>skin_js</type><file>js/TIG/PostNL/postcodecheck.js</file></action>
    <action method=\"addItem\"><type>skin_css</type><file>css/TIG/PostNL/postcodecheck.css</file></action>
    <block type=\"core/template\" name=\"postnl_validation\" template=\"TIG/PostNL/address_validation/validate.phtml\"/>
</reference>
<reference name=\"customer_address_edit\">
    <block type=\"core/template\" name=\"postnl_postcodecheck\" template=\"japi/TIG/PostNL/av/customer/address/postcode_check.phtml\"/>
</reference>";
        }

        if (Mage::helper('core')->isModuleEnabled('Sevenlike_Fatturazione')) {
            $xml .= "
<reference name=\"head\">
    <action method=\"addItem\"><type>skin_js</type><name>js/fatturazione.js</name></action>
    <action method=\"addItem\"><type>skin_js</type><name>onestepcheckout/js/autocomplete.js</name></action>
    <action method=\"addCss\"><stylesheet>onestepcheckout/autocomplete.css</stylesheet></action>
</reference>
<reference name=\"my.account.wrapper\">
    <block type=\"customer/address_edit\" name=\"customer_address_edit\" template=\"japi/fatturazione/customer/address/edit.phtml\"/>
</reference>";
        }

        try {
            $this->getLayout()->getUpdate()->addUpdate($xml);
            $this->generateLayoutXml()->generateLayoutBlocks();
        } catch (Exception $e) {
            Mage::logException($e);
        }
    }
}
