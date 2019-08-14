<?php

class Jmango360_Japi_Model_Dispatcher extends Mage_Api2_Model_Dispatcher
{
    public function dispatch(Mage_Api2_Model_Request $request, Mage_Api2_Model_Response $response)
    {
        ob_start();

        $model = $request->getModel();
        $model->dispatch();

        ob_end_clean();

        return $this;
    }
}
