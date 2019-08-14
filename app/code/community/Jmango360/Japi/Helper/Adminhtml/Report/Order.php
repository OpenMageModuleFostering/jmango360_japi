<?php

/**
 * Copyright 2015 JMango360
 */
class Jmango360_Japi_Helper_Adminhtml_Report_Order extends Mage_Adminhtml_Helper_Dashboard_Abstract
{
    protected function _initCollection()
    {
        $dateStart = $this->getParam('from');
        $dateEnd = $this->getParam('to');
        $range = $this->getParam('period_type');
        if (is_null($this->getParam('store_ids'))) {
            $this->setParam('store_ids', 0);
        }

        if ($dateStart && $dateEnd) {
            $this->_collection = Mage::getResourceSingleton('japi/report_order_collection')
                ->prepareSummary($range, $dateStart, $dateEnd, $this->getParam('live'));

            if ($this->getParam('store_ids')) {
                $this->_collection
                    ->addFieldToFilter('store_id', array('in' => explode(',', $this->getParam('store_ids'))));
            } else {
                if (!$this->getParam('live')) {
                    $this->_collection
                        ->addFieldToFilter('store_id', array('eq' => 0));
                }
            }

            if (count($this->getParam('order_statuses'))) {
                $this->_collection
                    ->addFieldToFilter(
                        $this->getParam('live') ? 'status' : 'order_status',
                        array('in' => $this->getParam('order_statuses'))
                    );
            }

            $this->_collection->load();
        }
    }

    public function getItems()
    {
        return is_array($this->getCollection())
            ? $this->getCollection()
            : (is_null($this->getCollection()) ? array() : $this->getCollection()->getItems());
    }
}
