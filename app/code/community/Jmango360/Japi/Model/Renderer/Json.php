<?php

class Jmango360_Japi_Model_Renderer_Json extends Mage_Api2_Model_Renderer_Json
{
    public function getMimeType()
    {
        return self::MIME_TYPE;
    }
}
