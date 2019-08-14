<?php

class Packpin_Pptrack_Block_Adminhtml_Sales_Order_Shipment_View_Tracking extends Mage_Adminhtml_Block_Sales_Order_Shipment_View_Tracking
{

    /**
     * Carrier list for static cache
     *
     * @var array
     */
    public static $carrierList = array();

    /**
     * Retrieve carriers
     *
     * @return array
     */
    public function getCarriers()
    {
        if (!self::$carrierList) {
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
                    $carriers[''] = Mage::helper('pptrack')->__('----- Packpin carriers -----');
                }
                foreach ($packpinCarriers as $item) {
                    $carriers[$item->getPrefixedCode()] = $item->getData('name');
                }
            }

            self::$carrierList = $carriers;
        }

        return self::$carrierList;
    }

    public function getCarrierTitle($code)
    {
        $enabled = Mage::getStoreConfig('pp_section_setttings/settings/status');
        if (!$enabled)
            return parent::getCarrierTitle($code);

        $allCarriers = $this->getCarriers();

        if (isset($allCarriers[$code]))
            return $allCarriers[$code];
        else
            return parent::getCarrierTitle($code);

    }

}