<?php
/**
 * Copyright 2016 JMango360
 */

/* @var $this Mage_Catalog_Model_Resource_Setup */
$this->startSetup();

// Add new column japi to grid table
$this->getConnection()->addColumn($this->getTable('sales/order_grid'), 'japi', 'int');

// Fill data
$select = $this->getConnection()->select();
$select->join(
    array('order_table' => $this->getTable('sales/order')),
    'order_table.entity_id = grid_table.entity_id',
    array('japi')
);
$this->getConnection()->query($select->crossUpdateFromSelect(array(
    'grid_table' => $this->getTable('sales/order_grid')
)));

$this->endSetup();
