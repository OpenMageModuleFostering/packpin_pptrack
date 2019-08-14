<?php

class Packpin_Pptrack_Block_Adminhtml_System_Config_Form_Fieldset_Carrier extends
    Mage_Adminhtml_Block_System_Config_Form_Fieldset
{
    protected $_dummyElement;
    protected $_fieldRenderer;
    protected $_values;
    protected $_dataSource;
    protected $_hidden = '';

    public function render(Varien_Data_Form_Element_Abstract $element)
    {
        $html = $this->_getHeaderHtml($element);

        $carriers = Mage::getModel('pptrack/carrier')->getList(true);

        //check all button
        $html .= '<tr><td class="label"></td><td>';
        $html .= $this->_getCheckAllButton('$$(\'.pp_carrier\').forEach(function(el){el.writeAttribute(\'checked\', true)});', $this->helper('pptrack')->__('Select All'));
        $html .= ' ';
        $html .= $this->_getCheckAllButton('$$(\'.pp_carrier\').forEach(function(el){el.writeAttribute(\'checked\', false)});', $this->helper('pptrack')->__('Unselect All'));
        $html .= '</td></tr>';

        $fields = '';
        foreach ($carriers as $carrier) {
            $fields .= $this->_getFieldHtml($element, $carrier);
        }
        $html .= $fields;
        $html .= $this->_getFooterHtml($element);

        return $this->_hidden . $html;
    }

    protected function _getFieldRenderer()
    {
        if (empty($this->_fieldRenderer)) {
            $this->_fieldRenderer = Mage::getBlockSingleton('adminhtml/system_config_form_field');
        }
        return $this->_fieldRenderer;
    }

    protected function _getValues()
    {
        if (!$this->_dataSource)
            $this->_dataSource = Mage::getSingleton('adminhtml/system_config_source_enabledisable')->toOptionArray();

        return $this->_dataSource;
    }

    protected function _getFieldHtml($fieldset, $carrier)
    {
        $name = 'groups[carriers][fields][' . $carrier->getCode() . '][value]';
        $this->_hidden .= '<input type="hidden" name="'. $name .'" value="0">';

        $field = $fieldset->addField($carrier->getCode(), 'checkbox',
            array(
                'name' => $name,
                'label' => $carrier->getName(),
                'class' => 'pp_carrier',
                'value' => 1,
                'checked' => $carrier->getEnabled(),
//                'values' => $this->_getValues(),
                'can_use_default_value' => 0,
                'can_use_website_value' => 0,
            ))->setRenderer($this->_getFieldRenderer());

        return $field->toHtml();
    }

    protected function _getCheckAllButton($action, $label)
    {
        $button = $this->getLayout()->createBlock('adminhtml/widget_button')
            ->setData(array(
                'label'     => $label,
                'onclick'   => 'javascript:'. $action .'; return false;'
            ));

        return $button->toHtml();
    }
}