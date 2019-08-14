<?php

/**
 * Copyright 2015 JMango360
 */
class Jmango360_Japi_Block_Adminhtml_Report_Chart_Customers extends Mage_Adminhtml_Block_Dashboard_Graph
{
    public function __construct()
    {
        parent::__construct();
        $this->setTemplate('japi/report/chart.phtml');
    }

    /**
     * Prepare chart data
     *
     * @return void
     */
    protected function _prepareData()
    {
        $this->setDataHelperName('japi/adminhtml_report_order');
        $this->getDataHelper()->setParams($this->getFilterData()->getData());
        $this->getDataHelper()->setParam('live', true);

        $this->setDataRows('customers_count');
        $this->_axisMaps = array(
            'x' => 'range',
            'y' => 'customers_count'
        );

        parent::_prepareData();
    }

    /**
     * Get chart url
     *
     * @param bool $directUrl
     * @return string
     */
    public function getChartUrl($directUrl = true)
    {
        $params = array(
            'cht' => 'lc',
            'chf' => 'bg,s,fafafa|c,lg,90,ffffff,0.1,ededed,0',
            'chm' => 'B,f4d4b2,0,0,0',
            'chco' => 'db4814'
        );

        $this->_allSeries = $this->getRowsData($this->_dataRows);

        foreach ($this->_axisMaps as $axis => $attr) {
            $this->setAxisLabels($axis, $this->getRowsData($attr, true));
        }

        $dateStart = strtotime($this->getDataHelper()->getParam('from'));
        $dateEnd = strtotime($this->getDataHelper()->getParam('to'));

        $dates = array();
        $datas = array();

        while ($dateStart <= $dateEnd) {
            $d = '';

            switch ($this->getDataHelper()->getParam('period_type')) {
                case 'day':
                    $d = date('Y-m-d', $dateStart);
                    $dateStart = strtotime('+1 day', $dateStart);
                    break;
                case 'month':
                    if (date('j', $dateStart) != 1) {
                        $dateStart = strtotime(date('Y-m-1', $dateStart));
                    }
                    $d = date('Y-m', $dateStart);
                    $dateStart = strtotime('+1 month', $dateStart);
                    break;
                case 'year':
                    if (date('n', $dateStart) != 1 || date('j', $dateStart) != 1) {
                        $dateStart = strtotime(date('Y-1-1', $dateStart));
                    }
                    $d = date('Y', $dateStart);
                    $dateStart = strtotime('+1 year', $dateStart);
                    break;
            }

            foreach ($this->getAllSeries() as $index => $serie) {
                if (in_array($d, $this->_axisLabels['x'])) {
                    $datas[$index][] = (float)array_shift($this->_allSeries[$index]);
                } else {
                    $datas[$index][] = 0;
                }
            }

            $dates[] = $d;
        }

        /**
         * setting skip step
         */
        if (count($dates) > 8 && count($dates) < 15) {
            $c = 1;
        } else if (count($dates) >= 15) {
            $c = 2;
        } else {
            $c = 0;
        }

        /**
         * skipping some x labels for good reading
         */
        $i = 0;
        foreach ($dates as $k => $d) {
            if ($i == $c) {
                $dates[$k] = $d;
                $i = 0;
            } else {
                $dates[$k] = '';
                $i++;
            }
        }

        $this->_axisLabels['x'] = $dates;
        $this->_allSeries = $datas;

        //Google encoding values
        if ($this->_encoding == "s") {
            // simple encoding
            $params['chd'] = "s:";
            $dataDelimiter = "";
            $dataSetdelimiter = ",";
            $dataMissing = "_";
        } else {
            // extended encoding
            $params['chd'] = "e:";
            $dataDelimiter = "";
            $dataSetdelimiter = ",";
            $dataMissing = "__";
        }

        // process each string in the array, and find the max length
        $localmaxvalue = array();
        $localminvalue = array();
        foreach ($this->getAllSeries() as $index => $serie) {
            $localmaxlength[$index] = sizeof($serie);
            $localmaxvalue[$index] = max($serie);
            $localminvalue[$index] = min($serie);
        }

        if (is_numeric($this->_max)) {
            $maxvalue = $this->_max;
        } else {
            $maxvalue = max($localmaxvalue);
        }

        if (is_numeric($this->_min)) {
            $minvalue = $this->_min;
        } else {
            $minvalue = min($localminvalue);
        }

        // default values
        $yrange = 0;
        $yLabels = array();
        $yorigin = 0;

        if ($minvalue >= 0 && $maxvalue >= 0) {
            $miny = 0;
            if ($maxvalue > 10) {
                $p = pow(10, $this->_getPow($maxvalue));
                $maxy = (ceil($maxvalue / $p)) * $p;
                $yLabels = range($miny, $maxy, $p);
            } else {
                $maxy = ceil($maxvalue + 1);
                $yLabels = range($miny, $maxy, 1);
            }
            $yrange = $maxy;
            $yorigin = 0;
        }

        $chartdata = array();

        foreach ($this->getAllSeries() as $index => $serie) {
            $thisdataarray = $serie;
            if ($this->_encoding == "s") {
                // SIMPLE ENCODING
                for ($j = 0; $j < sizeof($thisdataarray); $j++) {
                    $currentvalue = $thisdataarray[$j];
                    if (is_numeric($currentvalue)) {
                        $ylocation = round((strlen($this->_simpleEncoding) - 1) * ($yorigin + $currentvalue) / $yrange);
                        array_push($chartdata, substr($this->_simpleEncoding, $ylocation, 1) . $dataDelimiter);
                    } else {
                        array_push($chartdata, $dataMissing . $dataDelimiter);
                    }
                }
                // END SIMPLE ENCODING
            } else {
                // EXTENDED ENCODING
                for ($j = 0; $j < sizeof($thisdataarray); $j++) {
                    $currentvalue = $thisdataarray[$j];
                    if (is_numeric($currentvalue)) {
                        if ($yrange) {
                            $ylocation = (4095 * ($yorigin + $currentvalue) / $yrange);
                        } else {
                            $ylocation = 0;
                        }
                        $firstchar = floor($ylocation / 64);
                        $secondchar = $ylocation % 64;
                        $mappedchar = substr($this->_extendedEncoding, $firstchar, 1)
                            . substr($this->_extendedEncoding, $secondchar, 1);
                        array_push($chartdata, $mappedchar . $dataDelimiter);
                    } else {
                        array_push($chartdata, $dataMissing . $dataDelimiter);
                    }
                }
                // ============= END EXTENDED ENCODING =============
            }
            array_push($chartdata, $dataSetdelimiter);
        }

        $buffer = implode('', $chartdata);
        $buffer = rtrim($buffer, $dataSetdelimiter);
        $buffer = rtrim($buffer, $dataDelimiter);
        $buffer = str_replace(($dataDelimiter . $dataSetdelimiter), $dataSetdelimiter, $buffer);

        $params['chd'] .= $buffer;

        $valueBuffer = array();

        if (sizeof($this->_axisLabels) > 0) {
            $params['chxt'] = implode(',', array_keys($this->_axisLabels));
            $indexid = 0;
            foreach ($this->_axisLabels as $idx => $labels) {
                if ($idx == 'x') {
                    /**
                     * Format date
                     */
                    foreach ($this->_axisLabels[$idx] as $_index => $_label) {
                        if ($_label != '') {
                            switch ($this->getDataHelper()->getParam('period_type')) {
                                case 'day':
                                    $this->_axisLabels[$idx][$_index] = date('d/m/Y', strtotime($_label));
                                    break;
                                case 'month':
                                    $this->_axisLabels[$idx][$_index] = date('m/Y', strtotime($_label));
                                    break;
                                case 'year':
                                    $this->_axisLabels[$idx][$_index] = $_label;
                                    break;
                            }
                        } else {
                            $this->_axisLabels[$idx][$_index] = '';
                        }
                    }

                    $tmpstring = implode('|', $this->_axisLabels[$idx]);

                    $valueBuffer[] = $indexid . ":|" . $tmpstring;
                    if (sizeof($this->_axisLabels[$idx]) > 1) {
                        $deltaX = 100 / (sizeof($this->_axisLabels[$idx]) - 1);
                    } else {
                        $deltaX = 100;
                    }
                } else if ($idx == 'y') {
                    $valueBuffer[] = $indexid . ":|" . implode('|', $yLabels);
                    if (sizeof($yLabels) - 1) {
                        $deltaY = 100 / (sizeof($yLabels) - 1);
                    } else {
                        $deltaY = 100;
                    }
                }
                $indexid++;
            }

            $params['chxl'] = implode('|', $valueBuffer);
        };

        // chart size
        $params['chs'] = $this->getWidth() . 'x' . $this->getHeight();

        if (isset($deltaX) && isset($deltaY)) {
            $params['chg'] = $deltaX . ',' . $deltaY . ',1,0';
        }

        // return the encoded data
        if ($directUrl) {
            $p = array();
            foreach ($params as $name => $value) {
                $p[] = $name . '=' . urlencode($value);
            }
            return self::API_URL . '?' . implode('&', $p);
        } else {
            $gaData = urlencode(base64_encode(json_encode($params)));
            $gaHash = Mage::helper('adminhtml/dashboard_data')->getChartDataHash($gaData);
            $params = array('ga' => $gaData, 'h' => $gaHash);
            return $this->getUrl('adminhtml/japi_report/tunnel', array('_query' => $params));
        }
    }
}
