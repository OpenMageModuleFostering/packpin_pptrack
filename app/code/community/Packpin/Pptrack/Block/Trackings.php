<?php

class Packpin_Pptrack_Block_Trackings extends Mage_Core_Block_Template
{

    /**
     * @var Packpin_Pptrack_Model_Track
     */
    protected $_trackModel;

    /**
     * Check if Packpin tracks a shipment
     *
     * @param Mage_Sales_Model_Order_Shipment_Track $track
     * @param Mage_Sales_Model_Order $order
     * @return bool
     */
    public function shipmentTracked(Mage_Sales_Model_Order_Shipment_Track $track, Mage_Sales_Model_Order $order)
    {
        $collection = Mage::getModel('pptrack/track')
            ->getCollection()
            ->addFieldToFilter('order_id', array('eq' => $order->getId()))
            ->addFieldToFilter('shipment_id', array('eq' => $track->getId()));
        $this->_trackModel = $collection->getFirstItem();

        if ($this->_trackModel->getId())
            return true;

        return false;
    }

    /**
     * @return Packpin_Pptrack_Model_Track
     */
    public function getTrackModel()
    {
        return $this->_trackModel;
    }

}