<?php

/**
 * see for response codes http://www.restapitutorial.com/httpstatuscodes.html
 *
 */
class Jmango360_Japi_Model_Response extends Mage_Api2_Model_Response
{
    protected $_skipToken = false;

    public function setSkipToken($flag)
    {
        $this->_skipToken = (bool)$flag;
    }

    public function render(array $data)
    {
        if (!$this->_skipToken) {
            $data['token'] = Mage::getSingleton('japi/server')->getToken();
        }

        header("access-control-allow-origin: *");
        $this->setMimeType($this->getRenderer()->getMimeType())
            ->setBody($this->getRenderer()->render($data));
    }

    protected function getRenderer()
    {
        return Mage::getModel('japi/renderer_json');
    }
}
