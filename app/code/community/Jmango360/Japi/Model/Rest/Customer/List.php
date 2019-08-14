<?php

class Jmango360_Japi_Model_Rest_Customer_List extends Jmango360_Japi_Model_Rest_Customer
{
    /**
     * Retrieve customers data
     * @return array
     */
    public function getList()
    {
        $request = Mage::helper('japi')->getRequest();
        $email = $request->getParam('email', null);
        $name = $request->getParam('name_search', null);
        $customerIds = $request->getParam('customer_ids', array());
        $customerGroupIds = $request->getParam('customer_group_ids', array());
                
        $collection = Mage::getModel('customer/customer')->getCollection()->addAttributeToSelect('*');

        try {
            if (! empty($email)) {
                $collection->addFieldToFilter('email', array('like'=>$email));
            }
            
            if (! empty($name)) {
                $collection->addAttributeToFilter(
                    array(
                        array('attribute'=> 'firstname','like' => $name),
                        array('attribute'=> 'lastname','like' => $name),
                    )
                );
            }
            
            if (!empty($customerIds)) {
                $collection->addFieldToFilter('entity_id', array('IN'=>$customerIds));
            }
            
            if (!empty($customerGroupIds)) {
                $collection->addFieldToFilter('group_id', array('IN'=>$customerGroupIds));
            } 
        } catch (Mage_Core_Exception $e) {
            throw new Jmango360_Japi_Exception(Mage::helper('japi')->__('Could not retrieve list: ' . $e->getMessage()), Jmango360_Japi_Model_Request::HTTP_INTERNAL_ERROR);
        }
        
        $data = array();
        foreach ($collection as $customer) {
            $customer->unsPasswordHash();
            $row = $customer->toArray();
            foreach($customer->getAddresses() as $key => $address) {
                $row['adresses'][$key] = $address->toArray();
            }
            $data['customers'][] = $row;
        }
        
        return $data;
    }
}