<?php

/**
 * Copyright 2015 JMango360
 */
class Jmango360_Japi_Block_Adminhtml_System_Config_Form_Log extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    protected $_element;

    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('japi/system/config/form/log.phtml');
    }

    /**
     * Return element html
     *
     * @param  Varien_Data_Form_Element_Abstract $element
     * @return string
     */
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        $this->_element = $element;
        return $this->_toHtml();
    }

    /**
     * Render block HTML
     *
     * @return string
     */
    protected function _toHtml()
    {
        $html = $this->getLogSelectHtml();
        $html .= $this->getDownloadButtonHtml();
        return $html;
    }

    /**
     * @return string
     */
    protected function getLogSelectHtml()
    {
        $html = '<select id="' . $this->_element->getHtmlId() . '" onchange="japiChangeLog(this)">';
        $logDir = Mage::getBaseDir('var') . DS . 'log';
        foreach (scandir($logDir) as $fileName) {
            if ($fileName == '.' || $fileName == '..' || is_dir($logDir . DS . $fileName)) continue;
            $fileSize = $this->_getLogSize($logDir . DS . $fileName);
            $html .= '<option value="' . $fileName . '">' . sprintf('%s (%s)', $fileName, $fileSize) . '</option>';
        }
        $html .= '</select>';
        return $html;
    }

    /**
     * Generate button html
     *
     * @return string
     */
    protected function getDownloadButtonHtml()
    {
        $html = $this->getLayout()->createBlock('adminhtml/widget_button', '', array(
            'type' => 'button',
            'style' => 'margin-top:3px',
            'label' => $this->helper('japi')->__('Download'),
            'onclick' => sprintf('japiSubmitUrl(\'%s\')', $this->getUrl('adminhtml/japi_log/download'))
        ))->toHtml();

        $html .= $this->getLayout()->createBlock('adminhtml/widget_button', '', array(
            'id' => $this->_element->getHtmlId() . '_clear',
            'type' => 'button',
            'style' => 'margin-top:3px;margin-left:3px;',
            'label' => $this->helper('japi')->__('Clear'),
            'disabled' => true,
            'class' => 'disable',
            'onclick' => sprintf('japiSubmitUrl(\'%s\')', $this->getUrl('adminhtml/japi_log/clear'))
        ))->toHtml();

        $script = '<script type="text/javascript">
function japiSubmitUrl(url){
    var file = $("' . $this->_element->getHtmlId() . '").value;
    url += "?file=" + file;
    setLocation(url);
}
function japiChangeLog(el){
    if (el.value.indexOf("japi") === 0){
        enableElement($("' . $this->_element->getHtmlId() . '_clear' . '"));
    }else{
        disableElement($("' . $this->_element->getHtmlId() . '_clear' . '"));
    }
}
</script>';
        return $html . $script;
    }

    /**
     * Get SOAP log file size
     *
     * @param string $logFile
     * @return string
     */
    protected function _getLogSize($logFile = null)
    {
        if (!$logFile) {
            return '';
        }

        if (!file_exists($logFile)) {
            return '';
        }

        try {
            $logSize = filesize($logFile);
            if ($logSize == 0) return '0B';
            return $this->_humanFilesize($logSize);
        } catch (Exception $e) {
            return '';
        }
    }

    /**
     * @param int $bytes
     * @param int $decimals
     * @return string
     * @author Jeffrey Sambells
     * @url http://jeffreysambells.com/2012/10/25/human-readable-filesize-php
     */
    protected function _humanFilesize($bytes = 0, $decimals = 2)
    {
        $size = array('B', 'kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');
        $factor = floor((strlen($bytes) - 1) / 3);
        return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . @$size[$factor];
    }
}
