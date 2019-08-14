<?php

class Jmango360_Japi_Model_Rest_Customer_Login extends Jmango360_Japi_Model_Rest_Customer
{
    public function login()
    {
        $session = $this->_getSession();
        $data = array();

        if ($session->isLoggedIn()) {
            $login = $this->_getRequest()->getParam('login');
            if (!empty($login['username']) && $this->_getSession()->getCustomer()->getEmail() != $login['username']) {
                throw new Jmango360_Japi_Exception(
                    Mage::helper('japi')->__('Customer is already logged in.'),
                    Jmango360_Japi_Model_Request::REST_CUSTOMER_LOGGED_IN
                );
            }
        } else {
            if ($this->_getRequest()->isPost()) {
                $login = $this->_getRequest()->getParam('login');
                if (!empty($login['username']) && !empty($login['password'])) {
                    try {
                        $session->login($login['username'], $login['password']);
                    } catch (Mage_Core_Exception $e) {
                        switch ($e->getCode()) {
                            case Mage_Customer_Model_Customer::EXCEPTION_EMAIL_NOT_CONFIRMED:
                                $data = array(
                                    'messages' => array(
                                        'message' => array(
                                            array(
                                                'code' => Mage_Customer_Model_Customer::EXCEPTION_EMAIL_NOT_CONFIRMED,
                                                'message' => Mage::helper('japi')->__('Account is created. Please check email to confirm.'),
                                                'type' => 1
                                            )
                                        )
                                    )
                                );
                                return $data;
                                /*throw new Jmango360_Japi_Exception(
                                    Mage::helper('japi')->__('Account is created. Please check email to confirm.'),
                                    Jmango360_Japi_Model_Request::HTTP_INTERNAL_ERROR
                                );*/
                                break;
                            case Mage_Customer_Model_Customer::EXCEPTION_INVALID_EMAIL_OR_PASSWORD:
                                throw new Jmango360_Japi_Exception($e->getMessage(), Jmango360_Japi_Model_Request::HTTP_INTERNAL_ERROR);
                                break;
                            default:
                                throw new Jmango360_Japi_Exception($e->getMessage(), Jmango360_Japi_Model_Request::HTTP_INTERNAL_ERROR);
                        }
                    } catch (Exception $e) {
                        throw new Jmango360_Japi_Exception(
                            Mage::helper('japi')->__('Could not login') . ': ' . $e->getMessage(),
                            Jmango360_Japi_Model_Request::HTTP_INTERNAL_ERROR
                        );
                    }
                } else {
                    throw new Jmango360_Japi_Exception(
                        Mage::helper('japi')->__('Login and password are required.'),
                        Jmango360_Japi_Model_Request::HTTP_INTERNAL_ERROR
                    );
                }
            }
        }

        if ($this->_getSession()->isLoggedIn()) {
            /* @var $session Mage_Customer_Model_Session */
            $session = Mage::getSingleton('customer/session');
            /* @var $customer Mage_Customer_Model_Customer */
            $customer = $this->_getSession()->getCustomer();

            // Flag mobile user
            if (!$customer->getData('japi')) {
                $customer->setData('japi', 1)->save();
            }

            $session->setCustomerId($session->getId());
            $session->setCustomerGroupId($customer->getGroupId());

            $data['status'] = 'logged_in';
            $data['session_id'] = Mage::getSingleton('core/session')->getSessionId();
            $data['customer'] = $this->_getCustomerData();
        } else {
            throw new Jmango360_Japi_Exception(
                Mage::helper('japi')->__('Could not login'),
                Jmango360_Japi_Model_Request::HTTP_INTERNAL_ERROR
            );
        }

        return $data;
    }
}