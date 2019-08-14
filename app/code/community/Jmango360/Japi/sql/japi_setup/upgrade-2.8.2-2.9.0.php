<?php
/**
 * Copyright 2016 JMango360
 */

/* @var $this Mage_Catalog_Model_Resource_Setup */
$this->startSetup();

$this->addAttribute(Mage_Catalog_Model_Product::ENTITY, 'hide_in_jm360', array(
    'group' => 'General',
    'type' => 'int',
    'label' => 'Hide on JMango360 App',
    'input' => 'select',
    'source' => 'eav/entity_attribute_source_boolean',
    'global' => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE,
    'visible' => true,
    'required' => false,
    'user_defined' => false,
    'default' => '',
    'apply_to' => 'simple,configurable,grouped,bundle',
    'input_renderer' => '',
    'visible_on_front' => false,
    'used_in_product_listing' => false
));

$this->endSetup();
