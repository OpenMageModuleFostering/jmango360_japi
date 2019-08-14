<?php
/**
 * Copyright 2015 JMango360
 */

require_once 'Mage/Adminhtml/controllers/Report/SalesController.php';

class Jmango360_Japi_Adminhtml_Japi_ReportController extends Mage_Adminhtml_Report_SalesController
{
    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('japi/report');
    }

    protected function _init()
    {
        $this->loadLayout();
        $this->_setActiveMenu('japi');
        $this->_title(Mage::helper('japi')->__('JMango360'));
    }

    public function indexAction()
    {
        $type = $this->getRequest()->getParam('type');
        $dateFrom = Mage::app()->getLocale()->date();
        $dateTo = Mage::app()->getLocale()->date();
        $dateFormat = Mage::app()->getLocale()->getDateFormat(Mage_Core_Model_Locale::FORMAT_TYPE_SHORT);
        $dateFrom->subMonth(2);
        $dateFrom->setDay(1);

        switch ($type) {
            case 'orders':
                $params = array(
                    'report_type' => 'created_at_order',
                    'period_type' => 'month',
                    'from' => $dateFrom->toString($dateFormat),
                    'to' => $dateTo->toString($dateFormat),
                    'show_order_statuses' => 0,
                    'show_empty_rows' => 0,
                    'show_actual_columns' => 0
                );
                $this->_redirect('*/*/orders', array(
                        'filter' => urlencode(base64_encode(http_build_query($params))))
                );
                break;
            case 'sales':
                $params = array(
                    'report_type' => 'created_at_order',
                    'period_type' => 'month',
                    'from' => $dateFrom->toString($dateFormat),
                    'to' => $dateTo->toString($dateFormat),
                    'show_order_statuses' => 0,
                    'show_empty_rows' => 0,
                    'show_actual_columns' => 0
                );
                $this->_redirect('*/*/sales', array(
                        'filter' => urlencode(base64_encode(http_build_query($params))))
                );
                break;
            case 'customers':
                $params = array(
                    'report_type' => 'created_at_order',
                    'period_type' => 'month',
                    'from' => $dateFrom->toString($dateFormat),
                    'to' => $dateTo->toString($dateFormat),
                    'show_order_statuses' => 0,
                    'show_empty_rows' => 0,
                    'show_actual_columns' => 0
                );
                $this->_redirect('*/*/customers', array(
                        'filter' => urlencode(base64_encode(http_build_query($params))))
                );
                break;
        }
    }

    public function salesAction()
    {
        $this->_showLastExecutionTime(Mage_Reports_Model_Flag::REPORT_ORDER_FLAG_CODE, 'sales');

        $this->_init();
        $this->_title(Mage::helper('japi')->__('Sales Reports'));

        $gridBlock = $this->getLayout()->getBlock('adminhtml_report_sales.grid');
        $filterFormBlock = $this->getLayout()->getBlock('grid.filter.form');
        $chartBlock = $this->getLayout()->getBlock('report.chart');

        $this->_initReportAction(array(
            $gridBlock,
            $filterFormBlock,
            $chartBlock
        ));

        $this->renderLayout();
    }

    public function ordersAction()
    {
        $this->_showLastExecutionTime(Mage_Reports_Model_Flag::REPORT_ORDER_FLAG_CODE, 'sales');

        $this->_init();
        $this->_title(Mage::helper('japi')->__('Orders Reports'));

        $gridBlock = $this->getLayout()->getBlock('adminhtml_report_orders.grid');
        $filterFormBlock = $this->getLayout()->getBlock('grid.filter.form');
        $chartBlock = $this->getLayout()->getBlock('report.chart');

        $this->_initReportAction(array(
            $gridBlock,
            $filterFormBlock,
            $chartBlock
        ));

        $this->renderLayout();
    }

    public function customersAction()
    {
        $this->_init();
        $this->_title(Mage::helper('japi')->__('Customers Reports'));

        $gridBlock = $this->getLayout()->getBlock('adminhtml_report_customers.grid');
        $filterFormBlock = $this->getLayout()->getBlock('grid.filter.form');
        $chartBlock = $this->getLayout()->getBlock('report.chart');

        $this->_initReportAction(array(
            $gridBlock,
            $filterFormBlock,
            $chartBlock
        ));

        $this->renderLayout();
    }

    /**
     * Export sales report grid to CSV format
     */
    public function exportSalesCsvAction()
    {
        $fileName = 'sales_jmango360.csv';
        /* @var $grid Jmango360_Japi_Block_Adminhtml_Report_Sales_Grid */
        $grid = $this->getLayout()->createBlock('japi/adminhtml_report_sales_grid');
        $this->_initReportAction($grid);
        $this->_prepareDownloadResponse($fileName, $grid->getCsvFile());
    }

    /**
     * Export sales report grid to Excel XML format
     */
    public function exportSalesExcelAction()
    {
        $fileName = 'sales_jmango360.xml';
        /* @var $grid Jmango360_Japi_Block_Adminhtml_Report_Sales_Grid */
        $grid = $this->getLayout()->createBlock('japi/adminhtml_report_sales_grid');
        $this->_initReportAction($grid);
        $this->_prepareDownloadResponse($fileName, $grid->getExcelFile($fileName));
    }

    public function tunnelAction()
    {
        $httpClient = new Varien_Http_Client();
        $gaData = $this->getRequest()->getParam('ga');
        $gaHash = $this->getRequest()->getParam('h');
        if ($gaData && $gaHash) {
            $newHash = Mage::helper('adminhtml/dashboard_data')->getChartDataHash($gaData);
            if ($newHash == $gaHash) {
                $params = json_decode(base64_decode(urldecode($gaData)), true);
                if ($params) {
                    $response = $httpClient->setUri(Mage_Adminhtml_Block_Dashboard_Graph::API_URL)
                        ->setParameterGet($params)
                        ->setConfig(array('timeout' => 5))
                        ->request('GET');

                    $headers = $response->getHeaders();

                    $this->getResponse()
                        ->setHeader('Content-type', $headers['Content-type'], true)
                        ->setBody($response->getBody());
                }
            }
        }
    }
}
