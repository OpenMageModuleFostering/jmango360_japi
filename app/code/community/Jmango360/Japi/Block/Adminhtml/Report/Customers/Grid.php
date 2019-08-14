<?php

/**
 * Copyright 2015 JMango360
 */
class Jmango360_Japi_Block_Adminhtml_Report_Customers_Grid
    extends Mage_Adminhtml_Block_Report_Sales_Sales_Grid
{

    public function getResourceCollectionName()
    {
        return 'japi/sales_report_order_collection_live';
    }

    protected function _prepareColumns()
    {
        $this->addColumn('period', array(
            'header' => Mage::helper('sales')->__('Period'),
            'index' => 'period',
            'width' => 100,
            'sortable' => false,
            'period_type' => $this->getPeriodType(),
            'renderer' => 'adminhtml/report_sales_grid_column_renderer_date',
            'totals_label' => Mage::helper('sales')->__('Total'),
            'html_decorators' => array('nobr')
        ));

        $this->addColumn('customers_count', array(
            'header' => Mage::helper('sales')->__('Customers'),
            'index' => 'customers_count',
            'type' => 'number',
            'total' => 'sum',
            'sortable' => false
        ));

        $this->addColumn('orders_count', array(
            'header' => Mage::helper('sales')->__('Orders'),
            'index' => 'orders_count',
            'type' => 'number',
            'total' => 'sum',
            'sortable' => false
        ));

        $this->addExportType('*/*/exportSalesCsv', Mage::helper('adminhtml')->__('CSV'));
        $this->addExportType('*/*/exportSalesExcel', Mage::helper('adminhtml')->__('Excel XML'));

        return call_user_func(array(get_parent_class(get_parent_class($this)), '_prepareColumns'));
    }

    protected function _getAggregatedColumns()
    {
        if (is_null($this->_aggregatedColumns)) {
            foreach ($this->getColumns() as $column) {
                if (!is_array($this->_aggregatedColumns)) {
                    $this->_aggregatedColumns = array();
                }
                if ($column->hasTotal()) {
                    $this->_aggregatedColumns[$column->getId()] = "{$column->getTotal()}(r.{$column->getIndex()})";
                }
            }
        }
        return $this->_aggregatedColumns;
    }
}
