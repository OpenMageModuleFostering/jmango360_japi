<?php

/**
 * Copyright 2015 JMango360
 */
class Jmango360_Japi_Model_Resource_Report_Order_Collection extends Mage_Reports_Model_Resource_Order_Collection
{
    /**
     * Prepare report summary
     *
     * @param string $range
     * @param mixed $customStart
     * @param mixed $customEnd
     * @param boolean $live
     * @return $this
     */
    public function prepareSummary($range, $customStart, $customEnd, $live = false)
    {
        if ($live) {
            $this->_prepareSummaryLiveData($range, $customStart, $customEnd);
        } else {
            $this->_prepareSummaryAggregatedData($range, $customStart, $customEnd);
        }

        return $this;
    }

    /**
     * Prepare report summary from live data
     *
     * @param string $range
     * @param mixed $customStart
     * @param mixed $customEnd
     * @return $this
     */
    protected function _prepareSummaryLiveData($range, $customStart, $customEnd)
    {
        $this->setMainTable('sales/order');

        /**
         * Reset all columns, because result will group only by 'created_at' field
         */
        $this->getSelect()->reset(Zend_Db_Select::COLUMNS);

        $dateFrom = Mage::app()->getLocale()->date($customStart, Varien_Date::DATE_INTERNAL_FORMAT);
        $dateFrom->setHour(0);
        $dateFrom->setMinute(0);
        $dateFrom->setSecond(0);

        $dateTo = Mage::app()->getLocale()->date($customEnd, Varien_Date::DATE_INTERNAL_FORMAT);
        $dateTo->setHour(23);
        $dateTo->setMinute(59);
        $dateTo->setSecond(59);

        $tzRangeOffsetExpression = $this->_getTZRangeOffsetExpression(
            $range, 'created_at', $dateFrom, $dateTo
        );

        $this->getSelect()
            ->columns(array(
                'customers_count' => new Zend_Db_Expr(
                    sprintf('COUNT(DISTINCT %s)+SUM(if(%s IS NULL,1,0))',
                        'main_table.customer_id', 'main_table.customer_id'
                    )
                ),
                'range' => $tzRangeOffsetExpression
            ))
            ->where('main_table.state NOT IN (?)', array(
                Mage_Sales_Model_Order::STATE_PENDING_PAYMENT,
                Mage_Sales_Model_Order::STATE_NEW
            ))
            ->where('main_table.japi = ?', 1)
            ->order('range', Zend_Db_Select::SQL_ASC)
            ->group($tzRangeOffsetExpression);

        $this->addFieldToFilter('created_at', array(
            'from' => $dateFrom,
            'to' => $dateTo,
            'datetime' => true
        ));

        return $this;
    }

    /**
     * Prepare report summary from aggregated data
     *
     * @param string $range
     * @param mixed $customStart
     * @param mixed $customEnd
     * @return $this
     */
    protected function _prepareSummaryAggregatedData($range, $customStart, $customEnd)
    {
        $this->setMainTable('japi/sales_order_aggregated');
        /**
         * Reset all columns, because result will group only by 'created_at' field
         */
        $this->getSelect()->reset(Zend_Db_Select::COLUMNS);
        $rangePeriod = $this->_getRangeExpressionForAttribute($range, 'main_table.period');

        $tableName = $this->getConnection()->quoteIdentifier('main_table.period');
        $rangePeriod2 = str_replace($tableName, "MIN($tableName)", $rangePeriod);

        $this->getSelect()
            ->columns(array(
                'quantity' => 'SUM(main_table.orders_count)',
                'amount' => 'SUM(main_table.total_income_amount)',
                'range' => $rangePeriod2
            ))
            ->order('range')
            ->group($rangePeriod);

        $this->getSelect()->where(
            $this->_getConditionSql('main_table.period', array(
                'from' => $customStart,
                'to' => $customEnd,
                'datetime' => true
            ))
        );

        return $this;
    }

    /**
     * Get range expression
     *
     * @param string $range
     * @return Zend_Db_Expr
     */
    protected function _getRangeExpression($range)
    {
        switch ($range) {
            case 'day':
                return $this->getConnection()->getDateFormatSql('{{attribute}}', '%Y-%m-%d');
                break;
            default:
            case 'month':
                return $this->getConnection()->getDateFormatSql('{{attribute}}', '%Y-%m');
                break;
            case 'year':
                return $this->getConnection()->getDateFormatSql('{{attribute}}', '%Y');
                break;
        }
    }
}
