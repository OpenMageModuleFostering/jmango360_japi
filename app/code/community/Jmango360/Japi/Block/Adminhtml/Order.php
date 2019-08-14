<?php
/**
 * Copyright 2016 JMango360
 */

class Jmango360_Japi_Block_Adminhtml_Order extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    public function __construct()
    {
        parent::__construct();

        $this->_blockGroup = 'japi';
        $this->_controller = 'adminhtml_order';
        $this->_headerText = Mage::helper('japi')->__('Order List');
        $this->_removeButton('add');
    }
}