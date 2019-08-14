<?php

class Jmango360_Japi_Block_Adminhtml_Report_Filter_Form_Orders extends Jmango360_Japi_Block_Adminhtml_Report_Filter_Form
{
    protected function _prepareForm()
    {
        parent::_prepareForm();
        $this->getForm()->getElement('base_fieldset')->removeField('show_actual_columns');

        return $this;
    }
}
