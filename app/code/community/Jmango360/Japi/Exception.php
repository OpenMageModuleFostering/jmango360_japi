<?php
class Jmango360_Japi_Exception extends Exception
{
    /**
     * Exception constructor
     *
     * @param string $message
     * @param int $code
     */
    public function __construct($message, $code)
    {
        if ($code <= 100 || $code >= 599) {
            throw new Exception(sprintf(Mage::helper('japi')->__("Invalid Exception code '%d'", $code)));
        }

        if (is_String($message)) {
        	$message = Mage::helper('japi')->__($message);
        }
        parent::__construct($message, $code);
        
        
        /*
         * @TODO The request and response object saved in the core/session on exiting the script returns a fatal error; 
         *  -- should find a better way; maybe creating a rest owned session
         */
        $server = Mage::getSingleton('japi/server');
        $server->unsRequest();
        $server->unsResponse();
    }
}
