<?php

class Packpin_Pptrack_Model_Observer
{

    /**
     * For static cache
     *
     * @var array
     */
    protected $_config = array();

    /**
     * Event fired before Tracking popup view (in admin shipments)
     *
     * @param Varien_Event_Observer $observer
     * @return bool|void
     */
    public function trackingPopupPreDispatch(Varien_Event_Observer $observer)
    {
        $enabled = Mage::getStoreConfig('pp_section_setttings/settings/status');
        if (!$enabled)
            return false;

        $shippingTrackModel = null;
        $shippingList = null;

        $hash = Mage::app()->getRequest()->getParam('hash');
        $shippingInfoModel = Mage::getModel('shipping/info')->loadByHash($hash);
        $shippingTrackId = $shippingInfoModel->getTrackId();
        if ($shippingTrackId) {
            $shippingTrackModel = Mage::getModel('sales/order_shipment_track')->load($shippingTrackId);
        }
        else {
            $trackingInfo = $shippingInfoModel->getTrackingInfo();
            if ($trackingInfo) {
                $shippingList = current($trackingInfo);
            }
        }

        //for multiple shipment popup
        if ($shippingList) {
            $orderId = $shippingInfoModel->getOrderId();
            if (!$orderId) {
                $shipId = $shippingInfoModel->getShipId();
                if ($shipId) {
                    $shipModel = Mage::getModel('sales/order_shipment')->load($shipId);

                    $orderId = $shipModel->getOrderId();
                }
            }

            $models = array();
            foreach ($shippingList as $item) {
                $trackModel = Mage::getModel('pptrack/track')
                    ->loadByOrder($orderId, $item['number']);
                if ($trackModel->getId()) {
                    $models[] = $trackModel;
                }
            }

            if ($models) {
                Mage::register('models', $models);

                //redirect to our controller :)
                $request = Mage::app()->getRequest();
                $request->initForward()
                    ->setControllerName('index')
                    ->setModuleName('pptrack')
                    ->setActionName('popup')
                    ->setDispatched(false);

                return false;
            }
        }
        //for single shipment popup
        elseif ($shippingTrackModel->getId()) {
            $trackData = $shippingTrackModel->getData();
            $trackModel = Mage::getModel('pptrack/track')
                ->loadByOrder($trackData['order_id'], $this->_getTrackingCode($trackData));
            if ($trackModel->getId()) {
                Mage::register('model', $trackModel);

                //redirect to our controller :)
                $request = Mage::app()->getRequest();
                $request->initForward()
                    ->setControllerName('index')
                    ->setModuleName('pptrack')
                    ->setActionName('popup')
                    ->setDispatched(false);

                return false;
            }
        }
    }

    /**
     * Observe Event after new tracking has been added to shipment
     *
     * @param Varien_Event_Observer $observer
     * @return bool|void
     */
    public function afterShipmentSaved(Varien_Event_Observer $observer)
    {
        //plugin disabled
        $enabled = Mage::getStoreConfig('pp_section_setttings/settings/status');
        if (!$enabled)
            return false;

        $track = $observer->getEvent()->getTrack();
        $order = $track->getShipment()->getOrder();


        $trackData = $track->getData();
        $orderData = $order->getData();
        $addressData = $order->getShippingAddress()->getData();

        //detect carrier
        $carrierTitle = isset($trackData['title']) ? trim($trackData['title']) : null;
        $carrierCode = Mage::getModel('pptrack/carrier')
            ->detectCarrier($trackData['carrier_code'], $carrierTitle);
        if (!$carrierCode)
            return false;

        $trackingCode = $this->_getTrackingCode($trackData);
        $email = trim($orderData['customer_email']) ? $orderData['customer_email'] : null;
        $phone = trim($addressData['telephone']) ? $addressData['telephone'] : null;

        $collection = Mage::getModel('pptrack/track')
            ->getCollection()
            ->addFieldToFilter('code', array('eq' => $trackingCode))
            ->addFieldToFilter('order_id', array('eq' => $order->getId()));
        $trackModel = $collection->getFirstItem();
        $trackModel->setSubmitted(Packpin_Pptrack_Model_Track::TYPE_SUBMITTED_PENDING);

        if (!$trackModel->getId()) {
            $trackModel->setOrderId($order->getId());
            $trackModel->setShipmentId($track->getId());
            $trackModel->setCode($trackingCode);
            $trackModel->setCarrierCode($carrierCode);
            $trackModel->setCarrierName($trackData['title']);
            $trackModel->setPhone($phone);
            $trackModel->setEmail($email);

            //tracking attributes
            $trackModel->setPostalCode($addressData['postcode']);
            $trackModel->setDestinationCountry($addressData['country_id']);
//            $trackModel->setShipDate(date("Y-m-d"));

            $trackModel->setStatus(Packpin_Pptrack_Model_Track::STATUS_PENDING);
        }

        $trackModel->save();
        $trackModel->updateApi();
    }

    /**
     * Observe Event after new tracking has been removed from shipment
     *
     * @param Varien_Event_Observer $observer
     * @return bool|void
     */
    public function afterShipmentRemoved(Varien_Event_Observer $observer)
    {
        //plugin disabled
        $enabled = Mage::getStoreConfig('pp_section_setttings/settings/status');
        if (!$enabled)
            return false;

        $track = $observer->getEvent()->getTrack();
        $order = $track->getShipment()->getOrder();

        $trackData = $track->getData();
        $trackingCode = $this->_getTrackingCode($trackData);

        $collection = Mage::getModel('pptrack/track')
            ->getCollection()
            ->addFieldToFilter('code', array('eq' => $trackingCode))
            ->addFieldToFilter('order_id', array('eq' => $order->getId()));
        $trackModel = $collection->getFirstItem();

        if ($trackModel->getId()) {
            $trackModel->setSubmitted(Packpin_Pptrack_Model_Track::TYPE_SUBMITTED_REMOVE_PENDING);
            $trackModel->save();

            $trackModel->updateApi();
        }
    }

    /**
     * Get tracking code from Magento track data
     * Up to ver 1.6.2.0 `number` field is used
     * Later it's track_number
     *
     * @param array $trackData
     *
     * @return string
     */
    protected function _getTrackingCode($trackData)
    {
        $trackingCode = trim($trackData['track_number']) ? trim($trackData['track_number']) : trim($trackData['number']);

        return $trackingCode;
    }

    /**
     * @param Mage_Sales_Model_Order $order
     *
     * @return mixed
     */
    protected function _getConfig(Mage_Sales_Model_Order $order) {
        $websiteId = $order->getStore()->getWebsiteId();

        if (!isset($this->_config[$websiteId])) {
            $config = Mage::app()->getWebsite($websiteId)->getConfig('pp_section_setttings');

            $this->_config[$websiteId] = (object)((array)$config['settings']);
        }

        return $this->_config[$websiteId];
    }

    /**
     * Run cron and check if we have trackings that were not sent to API
     */
    public function cron()
    {
        $enabled = Mage::getStoreConfig('pp_section_setttings/settings/status');
        if (!$enabled)
            return false;

        set_time_limit(0);

        $list = Mage::getModel('pptrack/track')
            ->getCollection()
            ->addFieldToFilter('submitted', array('neq' => Packpin_Pptrack_Model_Track::TYPE_SUBMITTED_SENT))
            ->load();

        if ($list) {
            foreach ($list as $trackModel) {
                $trackModel->updateApi();
            }
        }
    }

    /**
     * Triggered after carriers are saved in admin configuration
     */
    public function saveCarriers(Varien_Event_Observer $observer)
    {
        $carriers = Mage::getModel('pptrack/carrier')->getList(true);
        foreach ($carriers as $carrier) {
            $configValue = (int) Mage::getStoreConfig('pp_section_carriers/carriers/' . $carrier->getCode());

            if ($carrier->enabled != $configValue) {
                $carrier->setEnabled($configValue);
                $carrier->save();
            }
        }
    }

}