<?php

/**
 * Copyright 2015 JMango360
 */
class Jmango360_Japi_Block_Adminhtml_Report_Sales_Chart extends Mage_Adminhtml_Block_Widget_Form
{
    protected function _prepareForm()
    {
        $form = new Varien_Data_Form();
        $fieldset = $form->addFieldset('chart_fieldset', array(
            'legend' => $this->__('Chart')
        ));
        $fieldset->addField('chart', 'text', array(
            'filter_data' => $this->getFilterData(),
            'graph_name' => 'japi/adminhtml_report_chart_sales'
        ));
        $form->getElement('chart')->setRenderer(
            $this->getLayout()->createBlock('japi/adminhtml_widget_form_renderer_element_chart')
        );
        $this->setForm($form);
        return parent::_prepareForm();
    }
}
