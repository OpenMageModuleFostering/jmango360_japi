<?php

class Jmango360_Japi_Model_Rest_Customer_Password extends Jmango360_Japi_Model_Rest_Customer
{
    public function passwordreset()
    {
        $email = (string)$this->_getRequest()->getParam('email');
        $data = array();

        if ($email) {
            if (!Zend_Validate::is($email, 'EmailAddress')) {
                throw new Jmango360_Japi_Exception(
                    Mage::helper('customer')->__('Invalid email address.'),
                    Jmango360_Japi_Model_Request::HTTP_INTERNAL_ERROR
                );
            }

            /** @var $customer Mage_Customer_Model_Customer */
            $customer = Mage::getSingleton('customer/customer')
                ->setWebsiteId(Mage::app()->getStore()->getWebsiteId())
                ->loadByEmail($email);

            if ($customer->getId()) {
                try {
                    $newResetPasswordLinkToken = Mage::helper('customer')->generateResetPasswordLinkToken();
                    $customer->changeResetPasswordLinkToken($newResetPasswordLinkToken);
                    $customer->sendPasswordResetConfirmationEmail();
                } catch (Exception $exception) {
                    throw new Jmango360_Japi_Exception(
                        $exception->getMessage(),
                        Jmango360_Japi_Model_Request::HTTP_INTERNAL_ERROR
                    );
                }
            }

            $data = array(
                'messages' => array(
                    'message' => array(
                        array(
                            'code' => Jmango360_Japi_Model_Request::HTTP_OK,
                            'message' => Mage::helper('customer')->__(
                                'If there is an account associated with %s you will receive an email with a link to reset your password.',
                                Mage::helper('customer')->escapeHtml($email)
                            ),
                            'type' => 1
                        )
                    )
                )
            );
        } else {
            throw new Jmango360_Japi_Exception(
                Mage::helper('customer')->__('Please enter your email.'),
                Jmango360_Japi_Model_Request::HTTP_BAD_REQUEST
            );
        }

        return $data;
    }
}
