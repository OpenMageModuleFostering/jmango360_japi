<?php

/**
 * Copyright 2015 JMango360
 */
if (@class_exists('Kega_Checkout_Block_Onepage')) {
    class Jmango360_Japi_Block_Checkout_Onepage extends Kega_Checkout_Block_Onepage
    {
    }
} else {
    class Jmango360_Japi_Block_Checkout_Onepage extends Mage_Checkout_Block_Onepage
    {
    }
}
