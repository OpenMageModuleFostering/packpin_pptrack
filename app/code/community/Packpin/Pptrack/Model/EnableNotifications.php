<?php

/**
 * Class Packpin_Pptrack_Model_Validation
 */
class Packpin_Pptrack_Model_Enablenotifications extends Mage_Core_Model_Config_Data
{

    public function save()
    {


        $helper = Mage::helper('pptrack');

        $apiKey = $this->_data['groups']['settings']['fields']['api_key']['value'];
        $pluginStatus = $this->_data['groups']['settings']['fields']['status']['value'];
        $notificationStatus = $this->_data['groups']['settings']['fields']['pp_enable_notifications']['value'];

        $oldApiKey = Mage::getStoreConfig('pp_section_setttings/settings/api_key');
        $oldPluginStatus = Mage::getStoreConfig('pp_section_setttings/settings/status');

        // Change API key config so we can get proper API key instantly
        Mage::app()->getStore()->setConfig('pp_section_setttings/settings/api_key', $apiKey);

        // Check if API key is missing
        if(!$apiKey) {
            Mage::throwException(Mage::helper('pptrack')->__('You have to enter API key before saving config!'));
        }

        //Check if status set to disabled and API key changed
        if(!$pluginStatus AND $oldApiKey !== $apiKey) {
            $info = $helper->testApiKey();

            if(!$info OR $info['statusCode'] == 400) {
                if ($info['body']['reason']) {
                    Mage::getSingleton('core/session')->addWarning(Mage::helper('pptrack')->__($info['body']['reason']));
                } else {
                    Mage::getSingleton('core/session')->addWarning(Mage::helper('pptrack')->__('Error sending data to API'));
                }
            }
        }

        // Check if plugin status set to Enable and API key changed then do API key check
        if(($pluginStatus AND $oldApiKey !== $apiKey) OR ($pluginStatus AND !$oldPluginStatus)) {
            $info = $helper->testApiKey();

            if(!$info OR $info['statusCode'] == 400) {
                if($info['body']['reason']) {
                    Mage::throwException(Mage::helper('pptrack')->__(Mage::helper('pptrack')->__($info['body']['reason'])));
                }
                else {
                    Mage::throwException(Mage::helper('pptrack')->__('Error sending data to API'));
                }
            }
        }

        // Check if plugin or notification status disabled then do API call to disable notifications connector
        if(!$pluginStatus OR !$notificationStatus) {
            $info = $helper->enableConnector('0');

            if(!$info OR $info['statusCode'] == 400 AND Mage::getStoreConfig('pp_section_setttings/settings/pp_enable_notifications') == 1) {
                if($info['body']['reason']) {
                    Mage::getSingleton('core/session')->addWarning(Mage::helper('pptrack')->__($info['body']['reason']));
                }
                else {
                    Mage::getSingleton('core/session')->addWarning(Mage::helper('pptrack')->__('Error sending data to API'));
                }
            }
        }

        // If API key exists and both plugin and notifications status set to enable call API to enable connector
        if($apiKey AND $pluginStatus AND $notificationStatus) {

            if($helper->createSoapUserAndRole($apiKey)) {
                $info = $helper->enableConnector($notificationStatus);

                if(!$info OR $info['statusCode'] == 400) {
                    if($info['body']['reason'] && !empty($info['body']['reason'])) {
                        Mage::throwException('Could not enable notifications: '.$info['body']['reason']);
                    }
                    else {
                        Mage::throwException('Could not enable notifications: Could not connect to API');
                    }
                }
            }
        }
        return parent::save();

    }
}