<?php

/**
 * Copyright 2015 JMango360
 */
class Jmango360_Japi_Block_Adminhtml_Report_Sales_Grid
    extends Mage_Adminhtml_Block_Report_Sales_Sales_Grid
{

    public function getResourceCollectionName()
    {
        return 'japi/sales_report_order_collection_aggregated';
    }

    protected function _prepareColumns()
    {
        parent::_prepareColumns();
        $this->removeColumn('orders_count');
        $this->removeColumn('total_qty_ordered');
        $this->removeColumn('total_qty_invoiced');

        return $this;
    }
}
