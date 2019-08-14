<?php

/**
 * Copyright 2015 JMango360
 */
class Jmango360_Japi_Block_Adminhtml_Report_Customers extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    public function __construct()
    {
        $this->_blockGroup = 'japi';
        $this->_controller = 'adminhtml_report_customers';
        $this->_headerText = Mage::helper('japi')->__('JMango360 Report - Total number of registered customers');

        parent::__construct();

        $this->setTemplate('report/grid/container.phtml');
        $this->_removeButton('add');
        $this->addButton('filter_form_submit', array(
            'label' => Mage::helper('reports')->__('Show Report'),
            'onclick' => 'filterFormSubmit()'
        ));
    }

    public function getFilterUrl()
    {
        $this->getRequest()->setParam('filter', null);
        return $this->getUrl('*/*/customers', array('_current' => true));
    }

    public function getHeaderCssClass()
    {
        return '';
    }
}
