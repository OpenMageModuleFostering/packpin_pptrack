<?php

/**
 * Class Packpin_Pptrack_Model_Setting
 *
 * Model for pp_settings table
 */
class Packpin_Pptrack_Model_Setting extends Mage_Core_Model_Abstract
{
    const SETTING_CARRIER_UPDATED = 'carrier_updated';

    protected function _construct()
    {
        $this->_init('pptrack/setting');
    }


    /**
     * Get setting value
     *
     * @param string $setting
     */
    public function loadModelBySetting($setting)
    {
        $this->load($setting, 'setting');
    }

}
