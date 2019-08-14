<?php

class Jmango360_Japi_Model_System_Config_Source_Customer_Attributes
{
    public function toOptionArray()
    {
        try {
            /* @var $model Jmango360_Japi_Model_Rest_Mage */
            $model = Mage::getModel('japi/rest_mage');
            $attributes = $model->getCustomerAttributes();
        } catch (Exception $e) {
            $attributes = array();
        }

        $options = array();
        $ignore = false;
        foreach ($attributes as $attribute) {
            if ($attribute['key'] == 'street[]') {
                if ($ignore) continue;
                $ignore = true;
                $options[] = array(
                    'value' => 'street',
                    'label' => sprintf('%s [%s]', $attribute['label'], 'street')
                );
            } else {
                $options[] = array(
                    'value' => $attribute['key'],
                    'label' => sprintf('%s [%s]', $attribute['label'], $attribute['key'])
                );
            }
        }

        return $options;
    }
}