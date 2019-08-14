<?php

class Packpin_Pptrack_Block_Index extends Mage_Core_Block_Template
{
    public $newTemplate = false;

    public $email = null;
    public $orderNumber = null;
    public $msg = null;
    public $orderId = null;

//    public $model = null;
    public $trackModels = array();


    protected function _construct()
    {
        parent::_construct();

        $model = Mage::getModel('pptrack/track');

        $hash = Mage::app()->getRequest()->getParam('h');
        $adminHash = Mage::app()->getRequest()->getParam('hash');
        if ($hash) {
            $model->loadInfoByHash($hash);
            $this->model = $model;
        }
        elseif ($adminHash) {

        }
        else {
            $this->newTemplate = true;

            $this->email = Mage::app()->getRequest()->getParam('email');
            $this->orderNumber = Mage::app()->getRequest()->getParam('order');
            //get track models
            if ($this->email && $this->orderNumber) {
                $order = Mage::getModel('sales/order')->loadByIncrementId($this->orderNumber);
                if (!$order) {
                    $this->msg = Mage::helper('pptrack')->__('Order not found');
                }
                elseif ($order->customer_email != $this->email) {
                    $this->orderId = $order->getId();

                    $this->msg = Mage::helper('pptrack')->__('Incorrect email');
                }
                else {
                    $this->orderId = $order->getId();
                    $shipments = $order->getShipmentsCollection();
                    if ($shipments && $shipments->count()) {
                        foreach ($shipments as $shipment) {
                            $tracks = $shipment->getAllTracks();
                            if (!$tracks)
                                continue;
                            foreach ($tracks as $track) {
                                $collection = Mage::getModel('pptrack/track')
                                    ->getCollection()
                                    ->addFieldToFilter('shipment_id', array('eq' => $track->getId()));
                                $trackModel = $collection->getFirstItem();

                                if (!$trackModel->getId()) {
                                    try {
                                        $carrierTitle = $track->title ? trim($track->title) : null;
                                        $carrierCode = Mage::getModel('pptrack/carrier')
                                            ->detectCarrier($track->carrier_code, $carrierTitle);

                                        $trackingCode = $track->track_number ? trim($track->track_number) : trim($track->number);
                                        $addressData = $order->getShippingAddress()->getData();

                                        $trackModel->setOrderId($order->getId());
                                        $trackModel->setShipmentId($track->getId());
                                        $trackModel->setCode($trackingCode);
                                        $trackModel->setCarrierCode($carrierCode);
                                        $trackModel->setCarrierName($carrierTitle);
                                        $trackModel->setEmail($order->customer_email);

                                        //tracking attributes
                                        $trackModel->setPostalCode($addressData['postcode']);
                                        $trackModel->setDestinationCountry($addressData['country_id']);

                                        $trackModel->setStatus(Packpin_Pptrack_Model_Track::STATUS_PENDING);
                                        $trackModel->save();
                                        $trackModel->updateApi();
                                    }
                                    catch (Exception $e) {
                                        continue;
                                    }
                                }
                                $trackModel->updateApiData();

                                $this->trackModels[] = $trackModel;
                            }
                        }
                    }

                    //log user session
                    try {
                        $trackId = $order->getId();
                        $ip = isset($_SERVER["REMOTE_ADDR"]) ? $_SERVER["REMOTE_ADDR"] : '';
                        $agent = isset($_SERVER["HTTP_USER_AGENT"]) ? $_SERVER["HTTP_USER_AGENT"] : null;

                        $userHash = md5($trackId . $ip . $agent);

                        $model = Mage::getModel('pptrack/visit')
                            ->getCollection()
                            ->addFieldToFilter('user_hash', array('eq' => $userHash))
                            ->addFieldToFilter("DATE_ADD(created_at, INTERVAL 1 DAY)", array('gteq' => date("Y-m-d H:i:s")))
                            ->getFirstItem();
                        if (!$model->getId()) {
                            $model->track_id = $trackId;
                            $model->user_hash = $userHash;
                            $model->user_ip = $ip;
                            $model->user_agent = $agent;
                            $model->save();
                        }
                    }
                    catch (Exception $e) {

                    }
                }

            }
        }

    }

}