<?php

$installer = $this;

$installer->startSetup();

//generate temp API key
$helper = Mage::helper('pptrack');
$key = Mage::getStoreConfig('pp_section_setttings/settings/api_key');

if (!$key) {
    try {
        $newKey = $helper->generateTempKey();

        if ($newKey) {
            $this->setConfigData('pp_section_setttings/settings/api_key', $newKey);
            $this->setConfigData('pp_section_setttings/settings/temp_api_key', $newKey);
            $this->setConfigData('pp_section_setttings/settings/temp_key_installed', time());
        }
        else {
            $this->setConfigData('pp_section_setttings/settings/status', 0);
        }
    } catch (Exception $e) {
        $this->setConfigData('pp_section_setttings/settings/status', 0);
    }
}

$installer->endSetup();





