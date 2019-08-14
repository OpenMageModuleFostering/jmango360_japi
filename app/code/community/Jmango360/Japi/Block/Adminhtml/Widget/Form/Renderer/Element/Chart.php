<?php

/**
 * Copyright 2015 JMango360
 */
class Jmango360_Japi_Block_Adminhtml_Widget_Form_Renderer_Element_Chart
    extends Mage_Adminhtml_Block_Widget_Form_Renderer_Element
{
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('japi/widget/form/renderer/element/chart.phtml');
    }

    public function getGraphHtml()
    {
        return $this->getLayout()->createBlock($this->getElement()->getGraphName(), '', array(
            'filter_data' => $this->getElement()->getFilterData()
        ))->toHtml();
    }
}
