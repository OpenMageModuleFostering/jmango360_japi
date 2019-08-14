<?php

/**
 * Copyright 2016 JMango360
 */

class Jmango360_Japi_Adminhtml_Japi_OrderController extends Mage_Adminhtml_Controller_Action
{
    /**
     * Init layout, menu and breadcrumb
     *
     * @return Jmango360_Japi_Adminhtml_Japi_OrderController
     */
    protected function _initAction()
    {
        $this->loadLayout()
            ->_setActiveMenu('japi/report/japi_order')
            ->_addBreadcrumb($this->__('Jmango360'), $this->__('Jmango360'))
            ->_addBreadcrumb($this->__('Reports'), $this->__('Reports'))
            ->_addBreadcrumb($this->__('Order Details'), $this->__('Order Details'));
        return $this;
    }

    /**
     * Japi Order grid
     */
    public function indexAction()
    {
        $this->_title($this->__('Japi Reports'))->_title($this->__('Order Details'));

        $this->_initAction()
            ->renderLayout();
    }

    /**
     * Japi Order grid
     */
    public function gridAction()
    {
        $this->loadLayout();
        $this->getResponse()->setBody(
            $this->getLayout()->getBlock('japi_order.grid')->toHtml()
        );
    }
}