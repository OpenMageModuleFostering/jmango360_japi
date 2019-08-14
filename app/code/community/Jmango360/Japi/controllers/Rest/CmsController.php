<?php

class Jmango360_Japi_Rest_CmsController extends Jmango360_Japi_Controller_Abstract
{
    public function getCmsPageListAction()
    {
        $this->loadLayout();
        $server = $this->getServer();
        $server->setRestDispatchModel(Mage::getModel('japi/rest_cms'));
        $server->run();
    }
}