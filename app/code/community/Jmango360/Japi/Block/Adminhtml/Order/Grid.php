<?php

/**
 * Copyright 2016 JMango360
 */
class Jmango360_Japi_Block_Adminhtml_Order_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    public function __construct()
    {
        parent::__construct();
        $this->setId('japi_order_grid');
        $this->setUseAjax(true);
        $this->setDefaultSort('created_at');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
    }

    protected function _prepareCollection()
    {
        $collection = Mage::getResourceModel('sales/order_collection')
            ->addFieldToFilter('japi', array('eq' => 1))
            ->join(
                array('b' => 'sales/order_address'),
                'main_table.entity_id = b.parent_id AND b.address_type = \'billing\'',
                array(
                    'billing_country_id' => 'b.country_id'
                )
            )
            ->join(
                array('s' => 'sales/order_address'),
                'main_table.entity_id = s.parent_id AND s.address_type = \'shipping\'',
                array(
                'shipping_country_id' => 's.country_id'
                )
            )
        ;

        $this->setCollection($collection);
        return parent::_prepareCollection();
        //return $this;
    }

    protected function _prepareColumns()
    {
        $helper = Mage::helper('japi');
        $currency = (string)Mage::getStoreConfig(Mage_Directory_Model_Currency::XML_PATH_CURRENCY_BASE);

        $this->addColumn('real_order_id', array(
            'header' => Mage::helper('sales')->__('Order #'),
            'width' => '80px',
            'type' => 'text',
            'index' => 'increment_id',
        ));

        $this->addColumn('created_at', array(
            'header' => $helper->__('Order Date'),
            'type' => 'datetime',
            'index' => 'created_at'
        ));

        $this->addColumn('billing_country', array(
            'header' => $helper->__('Billing Country'),
            'index' => 'billing_country_id',
            'type' => 'country',
            'renderer' => 'adminhtml/widget_grid_column_renderer_country',
            'filter_index' => 'b.country_id'
        ));

        $this->addColumn('shipping_country', array(
            'header' => $helper->__('Shipping Country'),
            'index' => 'shipping_country_id',
            'type' => 'country',
            'renderer' => 'adminhtml/widget_grid_column_renderer_country',
            'filter_index' => 's.country_id'
        ));

        $this->addColumn('total_item_count', array(
            'header' => $helper->__('Qty. Ordered'),
            'type' => 'number',
            'index' => 'total_item_count'
        ));

        $this->addColumn('subtotal', array(
            'header' => $helper->__('Subtotal'),
            'index' => 'subtotal',
            'type' => 'currency',
            'currency_code' => $currency
        ));

        $this->addColumn('grand_total', array(
            'header' => $helper->__('Total'),
            'index' => 'grand_total',
            'type' => 'currency',
            'currency_code' => $currency
        ));

        $this->addColumn('total_invoiced', array(
            'header' => $helper->__('Invoiced'),
            'index' => 'total_invoiced',
            'type' => 'currency',
            'currency_code' => $currency
        ));

        $this->addColumn('order_status', array(
            'header' => $helper->__('Status'),
            'index' => 'status',
            'type' => 'options',
            'options' => Mage::getSingleton('sales/order_config')->getStatuses(),
        ));

        if (Mage::getSingleton('admin/session')->isAllowed('sales/order/actions/view')) {
            $this->addColumn('action',
                array(
                    'header' => Mage::helper('sales')->__('Action'),
                    'width' => '50px',
                    'type' => 'action',
                    'getter' => 'getId',
                    'actions' => array(
                        array(
                            'caption' => Mage::helper('japi')->__('View'),
                            'url' => array('base' => '*/sales_order/view'),
                            'field' => 'order_id',
                            'data-column' => 'action',
                            'target' => '_blank'
                        )
                    ),
                    'filter' => false,
                    'sortable' => false,
                    'index' => 'stores',
                    'is_system' => true,
                ));
        }

        return parent::_prepareColumns();
    }

    public function getGridUrl()
    {
        return $this->getUrl('*/*/grid', array('_current' => true));
    }
}