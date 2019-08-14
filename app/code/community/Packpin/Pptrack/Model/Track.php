<?php

/**
 * Class Packpin_Pptrack_Model_Track
 *
 * Model for pp_tracks table
 */
class Packpin_Pptrack_Model_Track extends Mage_Core_Model_Abstract
{
    protected function _construct()
    {
        $this->_init('pptrack/track');
    }

    const STATUS_PENDING = 'pending';
    const STATUS_DELIVERED = 'delivered';
    const STATUS_OUT_FOR_DELIVERY = 'out_for_delivery';
    const STATUS_IN_TRANSIT = 'in_transit';
    const STATUS_INFO_RECEIVED = 'info_received';
    const STATUS_DISPATCHED_TO_OVERSEAS = 'to_overseas';
    const STATUS_FAILED_ATTEMPT = 'failed_attempt';
    const STATUS_EXCEPTION = 'exception';
    const STATUS_NO_INFO = 'no_info';


    /**
     * Model created but not sent to API
     */
    const TYPE_SUBMITTED_PENDING = 0;

    /**
     * Model data sent
     */
    const TYPE_SUBMITTED_SENT = 1;

    /**
     * Model pending to be removed
     */
    const TYPE_SUBMITTED_REMOVE_PENDING = 2;

    /**
     * Data fields we can map with API data
     *
     * @var array
     */
    public static $mapCols = array(
        'status',
    );

    /**
     * Response body for last API request
     *
     * @var array
     */
    public $apiResponseData;

    /**
     * Tracking details
     *
     * @var null|array
     */
    protected $_details;

    /**
     * Shipping Info
     *
     * @var null|array
     */
    protected $_shippingInfo;

    /**
     * Carrier model
     *
     * @var
     */
    protected $_carrier;

    /**
     * Get list of statuses with order number
     *
     * @return array
     */
    public static function getOrderedStatusList()
    {
        return array(
            self::STATUS_PENDING => 10,
            self::STATUS_NO_INFO => 10,
            self::STATUS_INFO_RECEIVED => 10,
            self::STATUS_IN_TRANSIT => 30,
            self::STATUS_DISPATCHED_TO_OVERSEAS => 30,
            self::STATUS_FAILED_ATTEMPT => 30,
            self::STATUS_EXCEPTION => 30,
            self::STATUS_OUT_FOR_DELIVERY => 50,
            self::STATUS_DELIVERED => 60,
        );
    }

    /**
     * @return array
     */
    public static function getStatusStrings()
    {
        return array(
            self::STATUS_PENDING => Mage::helper('pptrack')->__('Pending'),
            self::STATUS_NO_INFO => Mage::helper('pptrack')->__('No Info'),
            self::STATUS_INFO_RECEIVED => Mage::helper('pptrack')->__('In Transit'),
            self::STATUS_IN_TRANSIT => Mage::helper('pptrack')->__('In Transit'),
            self::STATUS_DISPATCHED_TO_OVERSEAS => Mage::helper('pptrack')->__('In Transit'),
            self::STATUS_FAILED_ATTEMPT => Mage::helper('pptrack')->__('Failed Attempt'),
            self::STATUS_EXCEPTION => Mage::helper('pptrack')->__('In Transit'),
            self::STATUS_OUT_FOR_DELIVERY => Mage::helper('pptrack')->__('Out For Delivery'),
            self::STATUS_DELIVERED => Mage::helper('pptrack')->__('Delivered'),
        );
    }

    /**
     * Update unix times
     *
     * @return mixed
     */
    protected function _beforeSave()
    {
        //new model
        if (!$this->getId()) {
            $this->hash = $this->_generateHash();
            $this->created_at = time();
        }
        $this->updated_at = time();

        return parent::_beforeSave();
    }

    /**
     * Generate UUID (v4) for usage in track details url backlink
     *
     * @return string
     */
    protected function _generateHash()
    {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',

            // 32 bits for "time_low"
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),

            // 16 bits for "time_mid"
            mt_rand(0, 0xffff),

            // 16 bits for "time_hi_and_version",
            // four most significant bits holds version number 4
            mt_rand(0, 0x0fff) | 0x4000,

            // 16 bits, 8 bits for "clk_seq_hi_res",
            // 8 bits for "clk_seq_low",
            // two most significant bits holds zero and one for variant DCE1.1
            mt_rand(0, 0x3fff) | 0x8000,

            // 48 bits for "node"
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }

    /**
     * Updates model info from API
     */
    protected function _fetchData()
    {
        $helper = Mage::helper('pptrack');
        $res = $helper->getTrackingInfo($this->getCarrierCode(), $this->getCode());

        //map new data to current model
        if ($res && $res["statusCode"] == 200) {
            $info = $res["body"];
            $this->apiResponseData = $info;

            $this->updateInfo($info);

            //Track details info
            if (isset($info['track_details']) && $info['track_details']) {
                //remove old details
                $list = $this->getDetails();
                foreach ($list as $model) {
                    $model->delete();
                }
                $this->_details = array();

                foreach ($info['track_details'] as $detailInfo) {
                    $model = Mage::getModel('pptrack/trackdetail');
                    $model->track_id = $this->getId();
                    $model->updateInfo($detailInfo);
                    $this->_details[] = $model;
                }
            }

        }
    }

    public function updateApiData()
    {
        $this->_fetchData();
    }

    /**
     * Update Track info
     *
     * @param array $data
     */
    public function updateInfo($data)
    {
        foreach (self::$mapCols as $field) {
            if (isset($data[$field]))
                $this->$field = $data[$field];
        }

        $this->save();
    }

    /**
     * Get Tracking info by order and tracking code
     *
     * @param int $orderId
     * @param string $code
     * @param bool $fetchData Fetch new data from API
     * @return $this
     */
    public function loadByOrder($orderId, $code, $fetchData = true)
    {
        $collection = Mage::getModel('pptrack/track')
            ->getCollection()
            ->addFieldToFilter('code', array('eq' => $code))
            ->addFieldToFilter('order_id', array('eq' => $orderId));
        $trackModel = $collection->getFirstItem();
        $this->setData($trackModel->getData());

        if ($this->getId() && $fetchData) {
            $this->_fetchData();
        }

        return $this;
    }

    /**
     * Get Tracking info by carrier and tracking code
     *
     * @param string $carrier
     * @param string $code
     * @return $this
     */
    public function loadByCarrierAndCode($carrier, $code)
    {
        $collection = Mage::getModel('pptrack/track')
            ->getCollection()
            ->addFieldToFilter('code', array('eq' => $code))
            ->addFieldToFilter('carrier_code', array('eq' => $carrier));
        $trackModel = $collection->getFirstItem();
        $this->setData($trackModel->getData());

        return $this;
    }

    /**
     * Get Tracking info by hash string
     *
     * @param string $hash
     * @param bool $fetchData Fetch new data from API
     */
    public function loadInfoByHash($hash, $fetchData = true)
    {
        $this->load($hash, 'hash');

        if ($this->getId() && $fetchData) {
            $this->_fetchData();
        }
    }

    /**
     * Get track details
     *
     * @return array
     */
    public function getDetails()
    {
        if ($this->_details === null) {
            if ($this->isEmpty()) {
                return array();
            }

            $this->_details = Mage::getModel('pptrack/trackdetail')
                ->getCollection()
                ->addFieldToFilter('track_id', array('eq' => $this->getId()))
                ->getItems();
        }

        return $this->_details;
    }

    /**
     * Get shipping information
     *
     * @return array
     */
    public function getShippingInfo()
    {
        if($this->_shippingInfo === null) {
            if ($this->isEmpty()) {
                return array();
            }

            $order = Mage::getModel('sales/order')->load($this->getOrderId());
            if ($order->getId()) {
                $address = $order->getShippingAddress();
                if ($address->getId()) {
                    $shippingInfo = $address->getData();

                    $this->_shippingInfo = $shippingInfo;
                }
            }
        }
        return $this->_shippingInfo;
    }

    /**
     * Get Track details url
     */
    public function getDetailsUrl()
    {
        $order = Mage::getModel('sales/order')->load($this->order_id);

//        $orders = Mage::getModel('sales/order')->getCollection()
//            ->setOrder('created_at','DESC')
//            ->addFieldToFilter('id', array('eq' => $this->order_id))
//            ->setPageSize(1)
//            ->setCurPage(1);
//        $order = $orders->getFirstItem();

        $url = Mage::getUrl('pptrack') . '?email=' . $order->customer_email . '&order=' . $order->getIncrementId();

        return $url;
    }

    public function getUnsubscribeLink()
    {
        $url = Mage::getUrl('pptrack/unsubscribe') . '?h=' . $this->hash;

        return $url;
    }

    /**
     * Try to sync this model with API
     * @param bool $waitForResponse
     */
    public function updateApi($waitForResponse = false)
    {
        $helper = Mage::helper('pptrack');

        $submitted = $this->getSubmitted();

        if ($submitted == Packpin_Pptrack_Model_Track::TYPE_SUBMITTED_PENDING) {
            $res = $helper->addTrackingCode(
                $this->getCarrierCode(),
                $this->getCode(),
                $this->getCarrierName(),
                $this->getPostalCode(),
                $this->getDestinationCountry(),
                $this->getShipDate(),
                $this->getOrderId(),
                $waitForResponse
            );

            //400 statuscode goes for "tracking already in the list"
            if ($res && in_array($res["statusCode"], array(201, 400))) {
                $this->setSubmitted(Packpin_Pptrack_Model_Track::TYPE_SUBMITTED_SENT);

                $this->save();
            }
        }
        elseif ($submitted == Packpin_Pptrack_Model_Track::TYPE_SUBMITTED_REMOVE_PENDING) {
            $res = $helper->removeTrackingCode(
                $this->getCarrierCode(),
                $this->getCode()
            );

            //404 goes for already removed
            if ($res && in_array($res["statusCode"], array(200, 204, 404))) {
                $this->delete();
            }
        }
    }

    /**
     * Get given status css class compared to current status
     *
     * @param $status
     * @return string
     */
    public function getStatusClass($status)
    {
        $list = self::getOrderedStatusList();

        if (!isset($list[$status]) || !isset($list[$this->getStatus()]))
            return '';

        if ($list[$status] < $list[$this->getStatus()])
            return 'done';
        elseif ($list[$status] == $list[$this->getStatus()])
            return 'active';

        return '';
    }

    /**
     * @return mixed
     */
    public function getOrderNumber()
    {
        $order = Mage::getModel('sales/order')->load($this->getOrderId());

        return $order->getIncrementId();
    }

    /**
     * Get carrier model
     *
     * @return array
     */
    public function getCarrier()
    {
        if ($this->_carrier === null) {
            $this->_carrier = Mage::getModel('pptrack/carrier')
                ->load($this->getCarrierCode(), 'code');
        }

        return $this->_carrier;
    }

    /**
     * @return string
     */
    public function getCarrierIcon()
    {
        $carrier = $this->getCarrier();
        if ($carrier && $carrier->getId()) {
            return $carrier->getIconUrl();
        }

        return '';
    }

    /**
     * @return string
     */
    public function getCarrierName()
    {
        $carrier = $this->getCarrier();
        if ($carrier && $carrier->getId()) {
            return $carrier->getName();
        }

        return '';
    }

    /**
     * @return string
     */
    public function getCarrierPhone()
    {
        $carrier = $this->getCarrier();
        if ($carrier && $carrier->getId()) {
            return $carrier->getPhone();
        }

        return '';
    }

    /**
     * @return string
     */
    public function getCarrierHomepage()
    {
        $carrier = $this->getCarrier();
        if ($carrier && $carrier->getId()) {
            return $carrier->getHomepage();
        }

        return '';
    }

    /**
     * @return string
     */
    public function getShippingDate($format = 'medium')
    {
        $time = $this->getCreatedAt();

        $dateFormatted = Mage::helper('core')->formatTime(date("Y-m-d H:i:s", $time), $format, true);
        return $dateFormatted;
    }

    public function getTotalCount()
    {
        $count = $this->getCollection()
            ->getSize();

        return $count;
    }

    public function getEstimatedDelivery()
    {
        if (isset($this->apiResponseData['estimated_delivery'])) {
            return Mage::helper('core')->formatDate($this->apiResponseData['estimated_delivery'], 'full', false);
        }
    }

    /**
     * Get package status as string
     * @return string
     */
    public function getStatusString()
    {
        $list = self::getStatusStrings();

        if (!isset($list[$this->getStatus()]))
            return '';

        return $list[$this->getStatus()];
    }

}
