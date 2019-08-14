<?php

class Packpin_Pptrack_Block_Adminhtml_System_Config_Fieldset_Hint
    extends Mage_Adminhtml_Block_Abstract
    implements Varien_Data_Form_Element_Renderer_Interface
{
    protected $_template = 'pptrack/system/config/fieldset/hint.phtml';

    /**
     * Render fieldset html
     *
     * @param Varien_Data_Form_Element_Abstract $element
     * @return string
     */
    public function render(Varien_Data_Form_Element_Abstract $element)
    {
        return $this->toHtml();
    }

    /**
     * Check if user still using temporary key
     *
     * @return bool
     */
    public function haveTempKey()
    {
        $key = Mage::getStoreConfig('pp_section_setttings/settings/api_key');
        $tempKey = Mage::getStoreConfig('pp_section_setttings/settings/temp_api_key');

        if ($tempKey && $tempKey == $key) {
            return true;
        }

        return false;
    }

    /**
     * Get days left till temp key expiration
     *
     * @return string
     */
    public function getDaysLeft()
    {
        $tempKeyInstalled = Mage::getStoreConfig('pp_section_setttings/settings/temp_key_installed');
        $timeDiff = $tempKeyInstalled + (86400 * 7) - time();
        if ($timeDiff > 0) {
            return ceil($timeDiff / 86400);
        }

        return 0;
    }
}

