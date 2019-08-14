<?php
include_once 'Mage/Checkout/controllers/OnepageController.php';

class Jmango360_Japi_TrollwebController extends Mage_Checkout_OnepageController
{
    public function preDispatch()
    {
        if (Mage::getStoreConfigFlag('japi/jmango_rest_developer_settings/enable')) {
            ini_set('display_errors', 1);
        }
        parent::preDispatch();
    }

    public function indexAction()
    {
        $this->loadLayout();
        $this->renderLayout();
    }
}
