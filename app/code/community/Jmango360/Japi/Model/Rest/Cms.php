<?php

class Jmango360_Japi_Model_Rest_Cms extends Mage_Core_Model_Abstract
{
    public function dispatch()
    {
        $action = $this->_getRequest()->getAction();
        $operation = $this->_getRequest()->getOperation();

        switch ($action . $operation) {
            case 'getCmsPageList' . Jmango360_Japi_Model_Request::OPERATION_RETRIEVE:
                $data = $this->_getCmsPageList();
                $this->_getResponse()->render($data);
                $this->_getResponse()->setHttpResponseCode(Jmango360_Japi_Model_Server::HTTP_OK);
                break;
            default:
                throw new Jmango360_Japi_Exception(Mage::helper('japi')->__('Resource method not implemented'), Jmango360_Japi_Model_Request::HTTP_INTERNAL_ERROR);
                break;
        }
    }

    protected function _getCmsPageList()
    {
        /* @var $model Jmango360_Japi_Model_Rest_Cms_Page */
        $model = Mage::getModel('japi/rest_cms_page');
        $data = $model->getList();

        return $data;
    }

    /**
     * @return Jmango360_Japi_Model_Request
     */
    protected function _getRequest()
    {
        return Mage::helper('japi')->getRequest();
    }

    /**
     * @return Jmango360_Japi_Model_Response
     */
    protected function _getResponse()
    {
        return Mage::helper('japi')->getResponse();
    }

    /**
     * @return Jmango360_Japi_Model_Server
     */
    protected function _getServer()
    {
        return Mage::helper('japi')->getServer();
    }
}
