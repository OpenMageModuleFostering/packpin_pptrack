<?php

/**
 * Class Packpin_Pptrack_Model_Trackdetail
 *
 * Model for pp_track_details table
 */
class Packpin_Pptrack_Model_Trackdetail extends Mage_Core_Model_Abstract
{
    protected function _construct()
    {
        $this->_init('pptrack/trackdetail');
    }

    /**
     * Data fields we can map with API data
     *
     * @var array
     */
    public static $mapCols = array(
        'carrier',
        'status',
        'status_string',
        'event_date',
        'event_time',
        'address',
        'country',
    );

    /**
     * Update Track detail info
     *
     * @param array $data
     */
    public function updateInfo($data)
    {
        foreach (self::$mapCols as $field) {
            if (isset($data[$field])) {
                if ($field == 'address') {
                    $params = array(
                        'address',
                        'state',
                        'zip',
                    );
                    $address = '';
                    foreach ($params as $param) {
                        if (isset($data[$param]) && $data[$param]) {
                            if ($address)
                                $address .= ', ';
                            $address .= ' ' . $data[$param];
                        }
                    }
                    $this->$field = $address;
                }
                else {
                    $this->$field = $data[$field];
                }
            }
        }

        $this->save();
    }

    /**
     * Get event location string
     *
     * @return mixed|string
     */
    public function getLocation()
    {
//        if ($this->address)
//            return $this->address;

        $params = array(
            'address',
            'country',
        );
        $address = '';
        foreach ($params as $param) {
            if ($this->$param) {
                if ($address)
                    $address .= ', ';
                $address .= ' ' . $this->$param;
            }
        }
        return $address;
    }


}