<?php

class Jmango360_Japi_Block_Adminhtml_Report_Filter_Form extends Mage_Sales_Block_Adminhtml_Report_Filter_Form_Order
{
    protected function _prepareForm()
    {
        parent::_prepareForm();
        $this->getForm()->getElement('base_fieldset')->removeField('report_type');

        return $this;
    }
}
