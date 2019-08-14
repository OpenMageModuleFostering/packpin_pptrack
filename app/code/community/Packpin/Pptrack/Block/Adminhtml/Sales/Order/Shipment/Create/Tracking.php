<?php

class Packpin_Pptrack_Block_Adminhtml_Sales_Order_Shipment_Create_Tracking extends Mage_Adminhtml_Block_Sales_Order_Shipment_Create_Tracking
{

    /**
     * Retrieve
     *
     * @return unknown
     */
    public function getCarriers()
    {
        $carriers = parent::getCarriers();

        $enabled = Mage::getStoreConfig('pp_section_setttings/settings/status');
        if (!$enabled)
            return $carriers;

        $disableDefault = isset($config['disable_default_carriers']) && $config['disable_default_carriers'] ? 1 : 0;

        //get Packpin Additional carriers
        $packpinCarriers = Mage::getModel('pptrack/carrier')
            ->getList();
        if ($packpinCarriers) {
            if ($disableDefault) {
                $carriers = array();
            }
            else {
                $carriers[''] = $this->__('----- Packpin carriers -----');
            }
            foreach ($packpinCarriers as $item) {
                $carriers[$item->getPrefixedCode()] = $item->getData('name');
            }
        }

        return $carriers;
    }

}