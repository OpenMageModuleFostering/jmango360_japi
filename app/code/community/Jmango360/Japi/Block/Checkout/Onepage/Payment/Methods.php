<?php

if (@class_exists('Flagbit_Checkout_Block_Onepage_Payment_Methods')) {
    class Jmango360_Japi_Block_Checkout_Onepage_Payment_Methods_Abstract extends Flagbit_Checkout_Block_Onepage_Payment_Methods
    {
    }
} else {
    class Jmango360_Japi_Block_Checkout_Onepage_Payment_Methods_Abstract extends Mage_Checkout_Block_Onepage_Payment_Methods
    {
    }
}

class Jmango360_Japi_Block_Checkout_Onepage_Payment_Methods extends Jmango360_Japi_Block_Checkout_Onepage_Payment_Methods_Abstract
{

}