<?php

/**
 * Copyright 2017 JMango360
 */
class Jmango360_Japi_Model_Payment_Paypal extends Varien_Http_Adapter_Curl
{
    const PAYPAL_SANDBOX_URL = 'https://api.sandbox.paypal.com/v1/';
    const PAYPAL_LIVE_URL = 'https://api.paypal.com/v1/';

    protected $_clientId;
    protected $_clientSecret;
    protected $_sandboxMode;
    protected $_headers;

    /**
     * Verify a payment ID from mobile Paypal SDK
     *
     * @param string $paymentId
     * @return bool
     * @throws Jmango360_Japi_Exception
     */
    public function verifyPayment($paymentId)
    {
        $accessToken = $this->getAccessToken();

        $this->setHeaders(array(
            'Content-Type: application/json',
            'Authorization: Bearer ' . $accessToken
        ));

        $rawResponse = $this->call(Zend_Http_Client::GET, sprintf('payments/payment/%s', $paymentId));
        if (!$rawResponse) {
            throw new Jmango360_Japi_Exception(
                Mage::helper('japi')->__('Could not get response'),
                Jmango360_Japi_Model_Request::HTTP_INTERNAL_ERROR
            );
        }

        $response = preg_split('/^\r?$/m', $rawResponse, 2);
        $response = json_decode(trim($response[1]));
        if ($response) {
            if ($response->error) {
                throw new Jmango360_Japi_Exception(
                    sprintf('[%s] %s', $response->error, $response->error_description),
                    Jmango360_Japi_Model_Request::HTTP_INTERNAL_ERROR
                );
            }
            if ($response->state == 'approved') {
                return true;
            } else {
                Mage::log($response, null, 'japi_paypal.log');
                throw new Jmango360_Japi_Exception(
                    Mage::helper('japi')->__('Paypal Payment (%s) not approved (%s)', $paymentId, $response->state),
                    Jmango360_Japi_Model_Request::HTTP_INTERNAL_ERROR
                );
            }
        } else {
            Mage::log($rawResponse, null, 'japi_paypal.log');
            throw new Jmango360_Japi_Exception(
                Mage::helper('japi')->__('Could not parse response'),
                Jmango360_Japi_Model_Request::HTTP_INTERNAL_ERROR
            );
        }
    }

    /**
     * Get access token from Paypal REST API
     *
     * @param null $clientId
     * @param null $clientSecret
     * @param null $sandboxMode
     * @return mixed
     * @throws Jmango360_Japi_Exception
     */
    public function getAccessToken($clientId = null, $clientSecret = null, $sandboxMode = null)
    {
        if (!$clientId && !$clientSecret && is_null($sandboxMode)) {
            $clientId = Mage::getStoreConfig('japi/jmango_rest_paypal_settings/client_id');
            $clientSecret = Mage::getStoreConfig('japi/jmango_rest_paypal_settings/client_secret');
            $this->_sandboxMode = Mage::getStoreConfig('japi/jmango_rest_paypal_settings/sandbox');
        } else {
            $this->_sandboxMode = $sandboxMode;
        }

        $this->setHeaders(array(
            'Accept: application/json',
            'Accept-Language: en_US'
        ));
        $this->setConfig(array(
            'timeout' => 60,
            'userpwd' => sprintf('%s:%s', $clientId, $clientSecret)
        ));
        $request = array(
            'grant_type' => 'client_credentials'
        );

        $response = $this->call(Zend_Http_Client::POST, 'oauth2/token', $request);

        if (!$response) {
            throw new Jmango360_Japi_Exception(
                Mage::helper('japi')->__('Could not get response'),
                Jmango360_Japi_Model_Request::HTTP_INTERNAL_ERROR
            );
        }

        $response = preg_split('/^\r?$/m', $response, 2);
        $response = json_decode(trim($response[1]));
        if ($response) {
            if ($response->error) {
                throw new Jmango360_Japi_Exception(
                    sprintf('[%s] %s', $response->error, $response->error_description),
                    Jmango360_Japi_Model_Request::HTTP_INTERNAL_ERROR
                );
            }
            if ($response->access_token) {
                return $response->access_token;
            }
        } else {
            throw new Jmango360_Japi_Exception(
                Mage::helper('japi')->__('Could not parse response'),
                Jmango360_Japi_Model_Request::HTTP_INTERNAL_ERROR
            );
        }
    }

    public function call($method = 'GET', $endpoint = '', $request = array())
    {
        try {
            $this->write(
                $method,
                $this->_getApiEndpoint($endpoint),
                '1.1',
                $this->getHeaders(),
                $this->_buildQuery($request)
            );
            return $this->read();
        } catch (Exception $e) {
            throw new Jmango360_Japi_Exception($e->getMessage(), $e->getCode());
        }
    }

    /**
     * Set CURL request headers
     *
     * @param array $headers
     * @return $this
     */
    public function setHeaders($headers = array())
    {
        $this->_headers = $headers;
        return $this;
    }

    /**
     * Get CURL request headers
     *
     * @return mixed
     */
    public function getHeaders()
    {
        return $this->_headers;
    }

    /**
     * API endpoint getter
     *
     * @param $endpoint
     * @return string
     */
    protected function _getApiEndpoint($endpoint)
    {
        return sprintf('%s%s', $this->_sandboxMode ? self::PAYPAL_SANDBOX_URL : self::PAYPAL_LIVE_URL, $endpoint);
    }

    /**
     * Build query string from request
     *
     * @param array $request
     * @return string
     */
    protected function _buildQuery($request)
    {
        return http_build_query($request);
    }
}