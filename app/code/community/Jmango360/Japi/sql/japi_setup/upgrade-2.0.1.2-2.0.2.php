<?php
/**
 * Copyright 2015 JMango360
 */

/* @var $this Mage_Catalog_Model_Resource_Setup */
$this->startSetup();

$installer = new Mage_Sales_Model_Resource_Setup('core_setup');
$installer->addAttribute('order', 'japi', array('type' => 'int'));
$installer->addAttribute('quote', 'japi', array('type' => 'int'));

/**
 * Create table 'japi/sales_order_aggregated'
 */
$tableName = $installer->getTable('japi/sales_order_aggregated');
if (!$installer->getConnection()->isTableExists($tableName)) {
    $table = $installer->getConnection()
        ->newTable($tableName)
        ->addColumn('id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
            'identity' => true,
            'unsigned' => true,
            'nullable' => false,
            'primary' => true,
        ), 'Id')
        ->addColumn('period', Varien_Db_Ddl_Table::TYPE_DATE, null, array(), 'Period')
        ->addColumn('store_id', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, array(
            'unsigned' => true,
        ), 'Store Id')
        ->addColumn('order_status', Varien_Db_Ddl_Table::TYPE_TEXT, 50, array(
            'nullable' => false,
            'default' => '',
        ), 'Order Status')
        ->addColumn('orders_count', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
            'nullable' => false,
            'default' => '0',
        ), 'Orders Count')
        ->addColumn('total_qty_ordered', Varien_Db_Ddl_Table::TYPE_DECIMAL, '12,4', array(
            'nullable' => false,
            'default' => '0.0000',
        ), 'Total Qty Ordered')
        ->addColumn('total_qty_invoiced', Varien_Db_Ddl_Table::TYPE_DECIMAL, '12,4', array(
            'nullable' => false,
            'default' => '0.0000',
        ), 'Total Qty Invoiced')
        ->addColumn('total_income_amount', Varien_Db_Ddl_Table::TYPE_DECIMAL, '12,4', array(
            'nullable' => false,
            'default' => '0.0000',
        ), 'Total Income Amount')
        ->addColumn('total_revenue_amount', Varien_Db_Ddl_Table::TYPE_DECIMAL, '12,4', array(
            'nullable' => false,
            'default' => '0.0000',
        ), 'Total Revenue Amount')
        ->addColumn('total_profit_amount', Varien_Db_Ddl_Table::TYPE_DECIMAL, '12,4', array(
            'nullable' => false,
            'default' => '0.0000',
        ), 'Total Profit Amount')
        ->addColumn('total_invoiced_amount', Varien_Db_Ddl_Table::TYPE_DECIMAL, '12,4', array(
            'nullable' => false,
            'default' => '0.0000',
        ), 'Total Invoiced Amount')
        ->addColumn('total_canceled_amount', Varien_Db_Ddl_Table::TYPE_DECIMAL, '12,4', array(
            'nullable' => false,
            'default' => '0.0000',
        ), 'Total Canceled Amount')
        ->addColumn('total_paid_amount', Varien_Db_Ddl_Table::TYPE_DECIMAL, '12,4', array(
            'nullable' => false,
            'default' => '0.0000',
        ), 'Total Paid Amount')
        ->addColumn('total_refunded_amount', Varien_Db_Ddl_Table::TYPE_DECIMAL, '12,4', array(
            'nullable' => false,
            'default' => '0.0000',
        ), 'Total Refunded Amount')
        ->addColumn('total_tax_amount', Varien_Db_Ddl_Table::TYPE_DECIMAL, '12,4', array(
            'nullable' => false,
            'default' => '0.0000',
        ), 'Total Tax Amount')
        ->addColumn('total_tax_amount_actual', Varien_Db_Ddl_Table::TYPE_DECIMAL, '12,4', array(
            'nullable' => false,
            'default' => '0.0000',
        ), 'Total Tax Amount Actual')
        ->addColumn('total_shipping_amount', Varien_Db_Ddl_Table::TYPE_DECIMAL, '12,4', array(
            'nullable' => false,
            'default' => '0.0000',
        ), 'Total Shipping Amount')
        ->addColumn('total_shipping_amount_actual', Varien_Db_Ddl_Table::TYPE_DECIMAL, '12,4', array(
            'nullable' => false,
            'default' => '0.0000',
        ), 'Total Shipping Amount Actual')
        ->addColumn('total_discount_amount', Varien_Db_Ddl_Table::TYPE_DECIMAL, '12,4', array(
            'nullable' => false,
            'default' => '0.0000',
        ), 'Total Discount Amount')
        ->addColumn('total_discount_amount_actual', Varien_Db_Ddl_Table::TYPE_DECIMAL, '12,4', array(
            'nullable' => false,
            'default' => '0.0000',
        ), 'Total Discount Amount Actual')
        ->addIndex(
            $installer->getIdxName(
                'japi/sales_order_aggregated',
                array('period', 'store_id', 'order_status'),
                Varien_Db_Adapter_Interface::INDEX_TYPE_UNIQUE
            ),
            array('period', 'store_id', 'order_status'),
            array('type' => Varien_Db_Adapter_Interface::INDEX_TYPE_UNIQUE)
        )
        ->addIndex(
            $installer->getIdxName('japi/sales_order_aggregated', array('store_id')),
            array('store_id')
        )
        ->addForeignKey(
            $installer->getFkName('japi/sales_order_aggregated', 'store_id', 'core/store', 'store_id'),
            'store_id',
            $installer->getTable('core/store'),
            'store_id',
            Varien_Db_Ddl_Table::ACTION_SET_NULL,
            Varien_Db_Ddl_Table::ACTION_CASCADE
        )
        ->setComment('Sales Order Aggregated From JMango360');
    $installer->getConnection()->createTable($table);
}

$this->endSetup();
