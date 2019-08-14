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

    /**
     * 1 = we track by order number and client email address
     * 2 = we track by carrier / tracking numbers
     * @var int
     */
    public $trackingType = 1;

    public $carrier;
    public $trackingNumbers;

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
            $this->carrier = Mage::app()->getRequest()->getParam('carrier');
            $this->trackingNumbers = Mage::app()->getRequest()->getParam('tracking_numbers');
            //get track models
            if ($this->email && $this->orderNumber) {
                $order = Mage::getModel('sales/order')->loadByIncrementId($this->orderNumber);
                if (!$order->getId()) {
                    $this->msg = Mage::helper('pptrack')->__('Order not found');
                }
                elseif ($order->customer_email != $this->email) {
                    $this->orderId = $order->getId();

                    $this->msg = Mage::helper('pptrack')->__('Incorrect email');
                }
                else {
                    $this->orderId = $order->getId();
                    $tracks = $order->getTracksCollection();
                    if ($tracks) {
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

            /**
             * Track by carrier / numbers
             */
            } elseif ($this->carrier && $this->trackingNumbers) {
                $this->trackingType = 2;
                // Can provide multiple numbers for same carrier
                $numbers = explode(',', $this->trackingNumbers);
                $this->trackingNumbers = $numbers;
                $this->carrier = strtolower($this->carrier);
                
                if ($this->trackingNumbers) {
                    foreach ($this->trackingNumbers as $trackingCode) {
                        $collection = Mage::getModel('pptrack/track')
                            ->getCollection()
                            ->addFieldToFilter('code', array('eq' => $trackingCode))
                            ->addFieldToFilter('carrier_code', array('eq' => $this->carrier));
                        $trackModel = $collection->getFirstItem();

                        if (!$trackModel->getId()) {
                            try {
                                $carrierTitle = $this->carrier;
                                $carrierCode = Mage::getModel('pptrack/carrier')
                                    ->detectCarrier($this->carrier);

                                $trackModel->setOrderId('');
                                $trackModel->setCode($trackingCode);
                                $trackModel->setCarrierCode($carrierCode);
                                $trackModel->setCarrierName($carrierTitle);

                                $trackModel->setStatus(Packpin_Pptrack_Model_Track::STATUS_PENDING);
                                $trackModel->save();
                                $trackModel->updateApi(true);
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
        }

    }

}