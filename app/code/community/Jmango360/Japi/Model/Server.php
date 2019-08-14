<?php

class Jmango360_Japi_Model_Server extends Mage_Api2_Model_Server
{
    const SESSIONIDACTIONNAME = 'getSession';
    const TOKENACTIONNAME = 'getToken';
    const CATEGORYCONTROLLERNAME = 'rest_category';
    const USETOKENPATH = 'japi/jmango_rest_api/use_token';

    protected $_excludeActionFromTokenValidation = array(
        self::SESSIONIDACTIONNAME,
        self::TOKENACTIONNAME,
        'getPluginVersion',
        'getMagentoInfo',
        'updateTheme',
        'success'
    );

    protected $_excludeControllerFromTokenValidation = array(
        self::CATEGORYCONTROLLERNAME,
    );

    protected $_excludeActionFromTokenReturn = array(
        'getPluginVersion',
        'getMagentoInfo',
        'updateTheme'
    );

    protected $_controllerInstance = null;

    protected $_model;

    public function run()
    {
        $request = Mage::app()->getRequest();

        // Log request if needed
        if (Mage::getStoreConfigFlag('japi/jmango_rest_developer_settings/enable')) {
            ini_set('display_errors', 1);

            $debug['uri'] = $request->getMethod() . ' ' . $request->getRequestUri();

            /* @var $session Mage_Core_Model_Session */
            $session = Mage::getSingleton('core/session');
            $debug['session_id'] = $session->getSessionId();
            $debug['params'] = $request->getParams();
            $debug['body'] = $request->getRawBody();

            Mage::log($debug, NULL, 'japi_request.log');
        }

        // Set current store if exist
        $storeId = $request->getParam('store_id', null);
        if ($storeId) {
            $this->_setCurrentStore($storeId);
            Mage::getSingleton('core/session')->setData('store_id', $storeId);
        } else {
            if ($storeId = Mage::getSingleton('core/session')->getData('store_id')) {
                $this->_setCurrentStore($storeId);
            }
        }

        foreach (Mage::app()->getWebsite()->getStores() as $store) {
            //Storing Flat product config value
            $session = Mage::getSingleton('core/session');
            $_flatConfig = Mage::getStoreConfigFlag(Mage_Catalog_Helper_Product_Flat::XML_PATH_USE_PRODUCT_FLAT);
            $session->setData('use_flat_product_' . $store->getId(), $_flatConfig);

            /**
             * Bypass flat product check
             * MPLUGIN-1777: Ignore when get related products
             */
            if ($request->getActionName() != 'getRelated' &&
                $request->getActionName() != 'getCrossSell' &&
                $request->getActionName() != 'getUpSell'
            ) {
                Mage::app()->getStore($store)->setConfig(Mage_Catalog_Helper_Product_Flat::XML_PATH_USE_PRODUCT_FLAT, 0);
            }

            // Bypass flat category check
            Mage::app()->getStore($store)->setConfig(Mage_Catalog_Helper_Category_Flat::XML_PATH_IS_ENABLED_FLAT_CATALOG_CATEGORY, 0);
        }

        // Checkout mobile version
        $mobileVersion = $request->getParam('version');
        $isOfflineCart = $mobileVersion ? version_compare($mobileVersion, '2.9', '<') : true;
        Mage::getSingleton('core/session')->setIsOffilneCart($isOfflineCart);

        // Flag can be used to determine if it is a REST service call
        $this->_setIsRest();

        // Can not use response object case
        try {
            /** @var $response Jmango360_Japi_Model_Response */
            $response = Mage::getSingleton('japi/response');
        } catch (Exception $e) {
            Mage::logException($e);

            if (!headers_sent()) {
                header('HTTP/1.1 ' . self::HTTP_INTERNAL_ERROR);
            }

            echo 'Service temporary unavailable';
            exit;
        }

        // Can not render errors case
        try {
            /** @var $request Jmango360_Japi_Model_Request */
            $request = Mage::getSingleton('japi/request');
            /** @var $renderer Jmango360_Japi_Model_Renderer_Json */
            $renderer = Mage::getModel('japi/renderer_json');
        } catch (Exception $e) {
            Mage::logException($e);

            if (!headers_sent()) {
                header('HTTP/1.1 ' . self::HTTP_INTERNAL_ERROR);
            }

            echo 'Service temporary unavailable';
            exit;
        }

        // Validate the token
        try {
            if (!$this->_validateToken()) {
                $message = Mage::helper('japi')->__('Invalid token');
                throw new Jmango360_Japi_Exception($message, Jmango360_Japi_Model_Request::REST_INVALID_TOKEN);
            }
        } catch (Exception $e) {
            $this->_renderException($e, $renderer, $response);
        }

        // Check if the right session ID is set
        try {
            if (!$this->_validateSessionId()) {
                throw new Jmango360_Japi_Exception(
                    Mage::helper('japi')->__('Session expired.'),
                    Jmango360_Japi_Model_Request::REST_SESSION_EXPIRED
                );
            }
        } catch (Exception $e) {
            $this->_renderException($e, $renderer, $response);
        }

        // Init empty admin user for Openwriter_Cartmart
        if (Mage::helper('core')->isModuleEnabled('Openwriter_Cartmart')) {
            Mage::getSingleton('admin/session')->setUser(Mage::getModel('admin/user'));
        }

        try {
            /*
             * $response could have an exception in session or token check
             */
            if (!$response->isException()) {
                $this->_setRequest($request);
                $this->_setResponse($response);

                if (!$this->getRestDispatchModel() || !is_object($this->getRestDispatchModel())) {
                    throw new Jmango360_Japi_Exception(Mage::helper('japi')->__('No rest model found.'), self::HTTP_INTERNAL_ERROR);
                }

                $action = Mage::app()->getRequest()->getActionName();
                if (in_array($action, $this->_excludeActionFromTokenReturn)) {
                    $response->setSkipToken(true);
                }

                $request->setModel($this->getRestDispatchModel());
                /* @var $dispatcher Jmango360_Japi_Model_Dispatcher */
                $dispatcher = Mage::getModel('japi/dispatcher');
                $dispatcher->dispatch($request, $response);
            }
        } catch (Exception $e) {
            Mage::logException($e);
            $this->_renderException($e, $renderer, $response);
        }

        Varien_Profiler::start('japi::server::send_response');
        $response->sendResponse();
        Varien_Profiler::stop('japi::server::send_response');

        /**
         * Remove objects from core/session
         *  -- objects cannot be serialized and the core/session is written/=serialized in the Mage_Core_Model_Resource_Session::__destruct
         */
        $this->unsRequest();
        $this->unsResponse();

        /**
         * Make sure response is not send again by Mage, because using the controller function; exit after send response.
         *  -- This prefends some __destruct triggers to work like $this->_saveCollectedStat() in varien autoload
         *  -- this doesnt seem to be a problem
         *  -- With errors the header(location:...) will not be passed, but that is something you cannot use because you dont use a webbrowser
         *  -- Zend_Log writers are not emptied. But these will be populated with the next call.
         */
        exit();
    }

    protected function _setCurrentStore($storeId)
    {
        if (!$storeId) return;
        if ($storeId == Mage::app()->getStore()->getId()) return;
        if ($storeId == -1) return;

        // Reset store ID
        Mage::app()->setCurrentStore($storeId);

        // Reset locale
        $locale = Mage::app()->getLocale();
        $locale->setDefaultLocale('');
        $locale->setLocaleCode($locale->getDefaultLocale());

        // Reset translator
        $transltor = Mage::app()->getTranslator();
        $transltor->setLocale($locale->getDefaultLocale());
        $area = $transltor->getConfig(Mage_Core_Model_Translate::CONFIG_KEY_AREA);
        $transltor->init($area, true);
    }

    public static function getApiTypes()
    {
        return self::$_apiTypes;
    }

    public function getRestDispatchModel()
    {
        return $this->_model;
    }

    public function setRestDispatchModel($model)
    {
        $this->_model = $model;
        return $this;
    }

    /**
     * Process thrown exception
     * Generate and set HTTP response code, error message to Response object
     *
     * @param Exception $exception
     * @param Mage_Api2_Model_Renderer_Interface $renderer
     * @param Mage_Api2_Model_Response $response
     * @return Mage_Api2_Model_Server
     */
    protected function _renderException(Exception $exception, Mage_Api2_Model_Renderer_Interface $renderer, Mage_Api2_Model_Response $response)
    {
        if ($exception instanceof Jmango360_Japi_Exception && $exception->getCode()) {
            $httpCode = $exception->getCode();
        } else {
            $httpCode = self::HTTP_INTERNAL_ERROR;
        }
        try {
            //add last error to stack
            $response->setException($exception);

            $messages = array();

            /** @var Exception $exception */
            foreach ($response->getException() as $exception) {
                $message = array(
                    'code' => $exception->getCode(),
                    'message' => strip_tags(implode("\n", array_unique(explode("\n", $exception->getMessage()))))
                );

                if (Mage::getIsDeveloperMode()) {
                    $message['trace'] = $exception->getTraceAsString();
                }

                $messages['messages']['error'][] = $message;
            }
            //set HTTP Code of last error, Content-Type and Body
            $response->setBody($renderer->render($messages));
            $response->setHeader('Content-Type', sprintf(
                '%s; charset=%s', $renderer->getMimeType(), Jmango360_Japi_Model_Response::RESPONSE_CHARSET
            ));
        } catch (Exception $e) {
            //tunnelling of 406(Not acceptable) error
            $httpCode = $e->getCode() == self::HTTP_NOT_ACCEPTABLE    //$e->getCode() can result in one more loop
                ? self::HTTP_NOT_ACCEPTABLE                      // of try..catch
                : self::HTTP_INTERNAL_ERROR;

            //if error appeared in "error rendering" process then show it in plain text
            $response->setBody($e->getMessage());
            $response->setHeader('Content-Type', 'text/plain; charset=' . Mage_Api2_Model_Response::RESPONSE_CHARSET);
        }

        /*
         * In the REST not registred HTTP codes are used. because this make reactions of netwerk devices and software unpredictable the error contains the REST status
         * -- but the real HTTP code is set to 500 (internal error)
         */
        if ($httpCode > 505) {
            $httpCode = 500;
        }

        $response->setHttpResponseCode($httpCode);

        return $this;
    }

    public function getToken()
    {
        return $this->_renewFormKey();
    }

    private function _validateToken()
    {
        if (!Mage::getStoreConfig(self::USETOKENPATH)) {
            return true;
        }

        $action = Mage::app()->getRequest()->getActionName();
        if (in_array($action, $this->_excludeActionFromTokenValidation)) {
            return true;
        }

        $controller = Mage::app()->getRequest()->getControllerName();
        if (in_array($controller, $this->_excludeControllerFromTokenValidation)) {
            return true;
        }

        if (Mage::getSingleton('core/session')->getIgnoreTokenCheck()) {
            Mage::getSingleton('core/session')->unsIgnoreTokenCheck();
            return true;
        }

        return $this->_validateFormKey();
    }

    private function _validateFormKey()
    {
        $test = Mage::getSingleton('core/session')->getFormKey();
        if (!($formKey = Mage::app()->getRequest()->getParam('token', null))
            || $formKey != Mage::getSingleton('core/session')->getFormKey()
        ) {
            return false;
        }
        return true;
    }

    private function _renewFormKey()
    {
        Mage::getSingleton('core/session')->setData('_form_key', '');
        return Mage::getSingleton('core/session')->getFormKey();
    }

    private function _validateSessionId()
    {
        if (!Mage::getStoreConfigFlag(Jmango360_Japi_Model_Rest_Mage::USEFRONTENDSIDPATH)) {
            return true;
        }

        $requestSessionId = Mage::app()->getRequest()->getParam('SID');
        $sessionSessionId = Mage::getSingleton('core/session')->getSessionId();
        if ($requestSessionId == $sessionSessionId) {
            return true;
        }

        /*
         * Action getSession renews the session ID and returns the new session ID
         * -- excluded from the check
         */
        $action = Mage::app()->getRequest()->getActionName();
        if (in_array($action, $this->_excludeActionFromTokenValidation)) {
            return true;
        }

        if (Mage::getSingleton('core/session')->getIgnoreSessionIdCheck()) {
            Mage::getSingleton('core/session')->unsIgnoreSessionIdCheck();
            return true;
        }

        return false;
    }

    private function _setRequest($request)
    {
        Mage::getModel('core/session')->setServerRequest($request);
    }

    private function _setResponse($response)
    {
        Mage::getModel('core/session')->setServerResponse($response);
    }

    /**
     * @return Jmango360_Japi_Model_Request
     */
    public function getRequest()
    {
        return Mage::getModel('core/session')->getServerRequest();
    }

    /**
     * @return Jmango360_Japi_Model_Response
     */
    public function getResponse()
    {
        return Mage::getModel('core/session')->getServerResponse();
    }

    public function unsRequest()
    {
        Mage::getModel('core/session')->unsServerRequest();
    }

    public function unsResponse()
    {
        Mage::getModel('core/session')->unsServerResponse();
    }

    protected function _setIsRest()
    {
        Mage::getSingleton('core/session')->setIsRest(true);

        return $this;
    }

    public function setIsRest()
    {
        $this->_setIsRest();
    }

    public function getIsRest()
    {
        return Mage::getSingleton('core/session')->getIsRest() || Mage::app()->getRequest()->getModuleName() == 'japi';
    }

    public function unsetIsRest()
    {
        Mage::getSingleton('core/session')->setIsRest(false);
    }

    public function setIsSubmit()
    {
        Mage::getSingleton('core/session')->setIsSubmit(true);
    }

    public function getIsSubmit()
    {
        return (bool)Mage::getSingleton('core/session')->getIsSubmit();
    }

    public function unsetIsSubmit()
    {
        Mage::getSingleton('core/session')->setIsSubmit(false);
    }

    public function setControllerInstance($instance)
    {
        $this->_controllerInstance = $instance;
    }

    public function getControllerInstance()
    {
        return $this->_controllerInstance;
    }
}