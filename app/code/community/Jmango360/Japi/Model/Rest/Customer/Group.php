<?php

class Jmango360_Japi_Model_Rest_Customer_Group extends Jmango360_Japi_Model_Rest_Customer
{
    protected $_customerAttributes = array(
        'firstname', 'lastname'
    );

    /**
     * Retrieve customer groups
     */
    public function getList()
    {
        /* @var $collection Mage_Customer_Model_Resource_Group_Collection */
        $collection = Mage::getResourceModel('customer/group_collection');

        $data = array();
        foreach ($collection as $group) {
            /* @var $group Mage_Customer_Model_Group */
            $data['groups'][] = array(
                'id' => $group->getId(),
                'code' => $group->getCode(),
                'label' => $group->getCode()
            );
        }

        return $data;
    }

    /**
     * Retrieve group customers
     */
    public function getCustomers()
    {
        $groupId = (int)$this->_getRequest()->getParam('group_id', 0);
        if (!is_numeric($groupId)) {
            throw new Jmango360_Japi_Exception(
                Mage::helper('japi')->__('Group ID not found.'),
                Jmango360_Japi_Model_Request::HTTP_INTERNAL_ERROR
            );
        }

        if ($groupId === 0) {
            return array('customers' => array());
        }

        /* @var $group Mage_Customer_Model_Customer */
        $group = Mage::getModel('customer/group')->load($groupId);
        if (!$group->getId()) {
            throw new Jmango360_Japi_Exception(
                Mage::helper('japi')->__('Group not found.'),
                Jmango360_Japi_Model_Request::HTTP_INTERNAL_ERROR
            );
        }

        /* @var $collection Mage_Customer_Model_Resource_Customer_Collection */
        $collection = Mage::getResourceModel('customer/customer_collection');
        $collection->addAttributeToSelect($this->_customerAttributes);
        $collection->addFieldToFilter('group_id', $group->getId());

        return $this->convertCollectionToResponse($collection);
    }

    /**
     * Search customers
     */
    public function search()
    {
        $query = $this->_getRequest()->getParam('q');
        if (!$query) {
            throw new Jmango360_Japi_Exception(
                Mage::helper('japi')->__('Query cannot be empty.'),
                Jmango360_Japi_Model_Request::HTTP_INTERNAL_ERROR
            );
        }

        /* @var $collection Mage_Customer_Model_Resource_Customer_Collection */
        $collection = Mage::getResourceModel('customer/customer_collection');
        $collection->addAttributeToSelect($this->_customerAttributes);
        $collection->addAttributeToFilter(array(
            array(
                'attribute' => 'firstname',
                'like' => '%' . $query . '%'
            ),
            array(
                'attribute' => 'lastname',
                'like' => '%' . $query . '%'
            ),
            array(
                'attribute' => 'email',
                'like' => '%' . $query . '%'
            )
        ));

        return $this->convertCollectionToResponse($collection);
    }

    /**
     * @param $collection Mage_Customer_Model_Resource_Customer_Collection
     * @return array
     */
    protected function convertCollectionToResponse($collection)
    {
        $page = $this->_getRequest()->getParam('p', 1);
        $page = is_numeric($page) ? $page : 1;
        $limit = $this->_getRequest()->getParam('limit', 20);
        $limit = is_numeric($limit) ? $limit : 20;
        $collection->setPage($page, $limit);

        $data = array(
            'page_num' => $collection->getCurPage(),
            'page_size' => $collection->getPageSize(),
            'page_total' => $collection->getLastPageNumber(),
            'customers' => array()
        );

        foreach ($collection as $customer) {
            /* @var $customer Mage_Customer_Model_Customer */
            $data['customers'][] = array(
                'firstname' => $customer->getData('firstname'),
                'lastname' => $customer->getData('lastname'),
                'email' => $customer->getData('email')
            );
        }

        return $data;
    }
}