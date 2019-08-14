<?php
/**
 * Copyright 2015 JMango360
 */

/* @var $this Mage_Catalog_Model_Resource_Setup */
$this->startSetup();

$customerEntityTypeId = $this->getEntityType('customer');

$this->addAttribute('customer', 'japi', array(
    'type' => 'varchar',
    'input' => 'text',
    'label' => 'JMango360',
    'visible' => false,
    'required' => false,
    'user_defined' => true,
    'visible_on_front' => false,
    'note' => 'User used Jmango360 mobile app'
));

$this->endSetup();
