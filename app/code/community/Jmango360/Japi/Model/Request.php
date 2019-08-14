<?php

class Jmango360_Japi_Model_Request extends Mage_Api2_Model_Request
{
    const OPERATION_CREATE = 'post';
    const OPERATION_RETRIEVE = 'get';
    const OPERATION_UPDATE = 'put';
    const OPERATION_DELETE = 'delete';

    const HTTP_OK = 200;
    const HTTP_CREATED = 201;
    const HTTP_MULTI_STATUS = 207;
    const HTTP_BAD_REQUEST = 400;
    const HTTP_UNAUTHORIZED = 401;
    const HTTP_FORBIDDEN = 403;
    const HTTP_NOT_FOUND = 404;
    const HTTP_METHOD_NOT_ALLOWED = 405;
    const HTTP_NOT_ACCEPTABLE = 406;
    const HTTP_INTERNAL_ERROR = 500;

    const REST_SESSION_EXPIRED = 520;
    const REST_INVALID_TOKEN = 521;

    const REST_CUSTOMER_EXPIRED = 530;
    const REST_CUSTOMER_LOGGED_IN = 531;

    const RESOURCE_METHOD_NOT_IMPLEMENTED = 'Resource method not implemented yet.';

    protected $_model = null;

    public function getAcceptTypes()
    {
        return "application/json";
    }

    public function getModel()
    {
        return $this->_model;
    }

    public function setModel($model)
    {
        $this->_model = $model;
        return $this;
    }

    public function getOperation()
    {
        if (!$this->isGet() && !$this->isPost() && !$this->isPut() && !$this->isDelete()) {
            throw new Mage_Api2_Exception('Invalid request method', Mage_Api2_Model_Server::HTTP_BAD_REQUEST);
        }
        // Map HTTP methods to classic CRUD verbs
        $operationByMethod = array(
            'GET' => self::OPERATION_RETRIEVE,
            'POST' => self::OPERATION_CREATE,
            'PUT' => self::OPERATION_UPDATE,
            'DELETE' => self::OPERATION_DELETE
        );

        return $operationByMethod[$this->getMethod()];
    }

    public function getAction()
    {
        return Mage::app()->getRequest()->getActionName();
    }

    public function getParams()
    {
        return Mage::app()->getRequest()->getParams();
    }

    public function getParam($key, $default = null)
    {
        return Mage::app()->getRequest()->getParam($key, $default);
    }

    public function setParam($key, $value)
    {
        Mage::app()->getRequest()->setParam($key, $value);
    }
}
