<?php

/**
 * Copyright 2015 JMango360
 */
class Jmango360_Japi_Model_Resource_Sales_Report_Order_Collection_Live
    extends Mage_Sales_Model_Resource_Report_Order_Collection
{
    /**
     * Aggregated Data Table
     *
     * @var string
     */
    protected $_aggregationTable = 'sales/order';

    /**
     * Get selected columns
     *
     * @return array
     */
    protected function _getSelectedColumns()
    {
        $adapter = $this->getConnection();

        $this->_selectedColumns = array(
            'store_id' => 'o.store_id',
            'orders_count' => new Zend_Db_Expr('COUNT(o.entity_id)'),
            'total_income_amount' => new Zend_Db_Expr(
                sprintf('SUM((%s - %s) * %s)',
                    $adapter->getIfNullSql('o.base_grand_total', 0),
                    $adapter->getIfNullSql('o.base_total_canceled', 0),
                    $adapter->getIfNullSql('o.base_to_global_rate', 0)
                )
            ),
            'total_revenue_amount' => new Zend_Db_Expr(
                sprintf('SUM((%s - %s - %s - (%s - %s - %s)) * %s)',
                    $adapter->getIfNullSql('o.base_total_invoiced', 0),
                    $adapter->getIfNullSql('o.base_tax_invoiced', 0),
                    $adapter->getIfNullSql('o.base_shipping_invoiced', 0),
                    $adapter->getIfNullSql('o.base_total_refunded', 0),
                    $adapter->getIfNullSql('o.base_tax_refunded', 0),
                    $adapter->getIfNullSql('o.base_shipping_refunded', 0),
                    $adapter->getIfNullSql('o.base_to_global_rate', 0)
                )
            ),
            'total_profit_amount' => new Zend_Db_Expr(
                sprintf('SUM((%s - %s - %s - %s - %s) * %s)',
                    $adapter->getIfNullSql('o.base_total_paid', 0),
                    $adapter->getIfNullSql('o.base_total_refunded', 0),
                    $adapter->getIfNullSql('o.base_tax_invoiced', 0),
                    $adapter->getIfNullSql('o.base_shipping_invoiced', 0),
                    $adapter->getIfNullSql('o.base_total_invoiced_cost', 0),
                    $adapter->getIfNullSql('o.base_to_global_rate', 0)
                )
            ),
            'total_invoiced_amount' => new Zend_Db_Expr(
                sprintf('SUM(%s * %s)',
                    $adapter->getIfNullSql('o.base_total_invoiced', 0),
                    $adapter->getIfNullSql('o.base_to_global_rate', 0)
                )
            ),
            'total_canceled_amount' => new Zend_Db_Expr(
                sprintf('SUM(%s * %s)',
                    $adapter->getIfNullSql('o.base_total_canceled', 0),
                    $adapter->getIfNullSql('o.base_to_global_rate', 0)
                )
            ),
            'total_paid_amount' => new Zend_Db_Expr(
                sprintf('SUM(%s * %s)',
                    $adapter->getIfNullSql('o.base_total_paid', 0),
                    $adapter->getIfNullSql('o.base_to_global_rate', 0)
                )
            ),
            'total_refunded_amount' => new Zend_Db_Expr(
                sprintf('SUM(%s * %s)',
                    $adapter->getIfNullSql('o.base_total_refunded', 0),
                    $adapter->getIfNullSql('o.base_to_global_rate', 0)
                )
            ),
            'total_tax_amount' => new Zend_Db_Expr(
                sprintf('SUM((%s - %s) * %s)',
                    $adapter->getIfNullSql('o.base_tax_amount', 0),
                    $adapter->getIfNullSql('o.base_tax_canceled', 0),
                    $adapter->getIfNullSql('o.base_to_global_rate', 0)
                )
            ),
            'total_tax_amount_actual' => new Zend_Db_Expr(
                sprintf('SUM((%s -%s) * %s)',
                    $adapter->getIfNullSql('o.base_tax_invoiced', 0),
                    $adapter->getIfNullSql('o.base_tax_refunded', 0),
                    $adapter->getIfNullSql('o.base_to_global_rate', 0)
                )
            ),
            'total_shipping_amount' => new Zend_Db_Expr(
                sprintf('SUM((%s - %s) * %s)',
                    $adapter->getIfNullSql('o.base_shipping_amount', 0),
                    $adapter->getIfNullSql('o.base_shipping_canceled', 0),
                    $adapter->getIfNullSql('o.base_to_global_rate', 0)
                )
            ),
            'total_shipping_amount_actual' => new Zend_Db_Expr(
                sprintf('SUM((%s - %s) * %s)',
                    $adapter->getIfNullSql('o.base_shipping_invoiced', 0),
                    $adapter->getIfNullSql('o.base_shipping_refunded', 0),
                    $adapter->getIfNullSql('o.base_to_global_rate', 0)
                )
            ),
            'total_discount_amount' => new Zend_Db_Expr(
                sprintf('SUM((ABS(%s) - %s) * %s)',
                    $adapter->getIfNullSql('o.base_discount_amount', 0),
                    $adapter->getIfNullSql('o.base_discount_canceled', 0),
                    $adapter->getIfNullSql('o.base_to_global_rate', 0)
                )
            ),
            'total_discount_amount_actual' => new Zend_Db_Expr(
                sprintf('SUM((%s - %s) * %s)',
                    $adapter->getIfNullSql('o.base_discount_invoiced', 0),
                    $adapter->getIfNullSql('o.base_discount_refunded', 0),
                    $adapter->getIfNullSql('o.base_to_global_rate', 0)
                )
            ),
            'customers_count' => new Zend_Db_Expr(
                sprintf('COUNT(DISTINCT %s) + SUM(IF(%s IS NULL, 1, 0))',
                    $adapter->quoteIdentifier('o.customer_id'),
                    $adapter->quoteIdentifier('o.customer_id')
                )
            )
        );

        return $this->_selectedColumns;
    }

    /**
     * Add selected data
     *
     * @return Mage_Sales_Model_Resource_Report_Order_Collection
     */
    protected function _initSelect()
    {
        $this->getSelect()
            ->from(array('o' => $this->getResource()->getMainTable()), $this->_getSelectedColumns())
            ->where('o.state NOT IN (?)', array(
                Mage_Sales_Model_Order::STATE_PENDING_PAYMENT,
                Mage_Sales_Model_Order::STATE_NEW
            ))
            ->where('o.japi = ?', 1)
            ->group(array(
                'o.store_id'
            ));

        return $this;
    }

    /**
     * Apply date range filter
     *
     * @return Mage_Sales_Model_Resource_Report_Collection_Abstract
     */
    protected function _applyDateRangeFilter()
    {
        if ($this->_period === null && $this->_to === null) {
            return $this;
        }

        $dateFrom = Mage::app()->getLocale()->date($this->_from, Varien_Date::DATE_INTERNAL_FORMAT);
        $dateFrom->setHour(0);
        $dateFrom->setMinute(0);
        $dateFrom->setSecond(0);

        $dateTo = Mage::app()->getLocale()->date($this->_to, Varien_Date::DATE_INTERNAL_FORMAT);
        $dateTo->setHour(23);
        $dateTo->setMinute(59);
        $dateTo->setSecond(59);

        $this->getSelect()
            ->columns(array(
                'period' => $this->_getTZRangeOffsetExpression(
                    $this->_period, 'created_at', $dateFrom, $dateTo
                )
            ))
            ->where(
                $this->_getConditionSql('o.created_at', array(
                    'from' => $dateFrom,
                    'to' => $dateTo,
                    'datetime' => true
                ))
            )
            ->group('period');

        return $this;
    }

    /**
     * Retrieve query for attribute with timezone conversion
     *
     * @param string $range
     * @param string $attribute
     * @param mixed $from
     * @param mixed $to
     * @return string
     */
    protected function _getTZRangeOffsetExpression($range, $attribute, $from = null, $to = null)
    {
        return str_replace(
            '{{attribute}}',
            Mage::getResourceModel('sales/report_order')
                ->getStoreTZOffsetQuery($this->getMainTable(), $attribute, $from, $to),
            $this->_getRangeExpression($range)
        );
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

    /**
     * Custom filters application ability
     *
     * @return Mage_Reports_Model_Resource_Report_Collection_Abstract
     */
    protected function _applyCustomFilter()
    {
        $this->_applyOrderStatusFilter();

        if ($this->isTotals()) {
            $subSelect = clone $this->getSelect();
            $this->getSelect()
                ->reset()
                ->from(array('r' => $subSelect), $this->getAggregatedColumns());
        }

        return $this;
    }

    /**
     * Apply order status filter
     *
     * @return Mage_Sales_Model_Resource_Report_Collection_Abstract
     */
    protected function _applyOrderStatusFilter()
    {
        if (is_null($this->_orderStatus)) {
            return $this;
        }

        $orderStatus = $this->_orderStatus;
        if (!is_array($orderStatus)) {
            $orderStatus = array($orderStatus);
        }

        $this->getSelect()->where('o.status IN(?)', $orderStatus);

        return $this;
    }
}
