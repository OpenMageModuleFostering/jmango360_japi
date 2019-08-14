<?php

class Jmango360_Japi_Block_Adminhtml_Catalog_Product_Grid_Column_Renderer_Hide extends
    Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Options
{
    public function render(Varien_Object $row)
    {
        $value = $row->getData($this->getColumn()->getIndex());
        $row->setData($this->getColumn()->getIndex(), !$value ? 0 : 1);
        return parent::render($row);
    }
}