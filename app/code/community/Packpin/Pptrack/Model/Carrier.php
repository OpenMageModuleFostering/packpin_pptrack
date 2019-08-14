<?php

/**
 * Class Packpin_Pptrack_Model_Carrier
 *
 * Model for pp_carriers table
 */
class Packpin_Pptrack_Model_Carrier extends Mage_Core_Model_Abstract
{
    /**
     * Update once per day
     */
    const API_UPDATE_INTERVAL = 86400;

    /**
     * Append carrier code with prefix to prevent conflicts
     */
    const CODE_PREFIX = 'pp_';

    const URL_ICONS = 'https://button.packpin.com/assets/images/carriers_v2/';

    public $assocList = array();

    protected function _construct()
    {
        $this->_init('pptrack/carrier');
    }

    /**
     * Get carrier list if needed - update from API
     * @param bool $fetchAll
     * @return array
     */
    public function getList($fetchAll = false)
    {
        $collection = Mage::getModel('pptrack/carrier')
            ->getCollection();

        if (!$fetchAll)
            $collection->addFieldToFilter('enabled', array('eq' => 1));

        $carriers = $collection->getItems();

        //check last updated
        $config = Mage::getStoreConfig('pp_section_setttings/settings');
        $updated = isset($config['last_carrier_update']) ? $config['last_carrier_update'] : null;

        //try to get info from API
        if (!$carriers || !$updated || $updated + self::API_UPDATE_INTERVAL < time()) {
            $helper = Mage::helper('pptrack');
            $res = $helper->getCarrierList();
            if ($res && $res["statusCode"] == 200) {
                $list = $res["body"];

                if (is_array($list)) {
                    $newList = array();

                    //prepare old list data for updates
                    $oldList = array();
                    foreach ($carriers as $item) {
                        $oldList[$item->code] = $item;
                    }

                    foreach ($list as $item) {
                        $model = Mage::getModel('pptrack/carrier')
                            ->load($item['code'], 'code');

                        if ($model->getId()) {
                            if (isset($oldList[$item['code']]))
                                unset($oldList[$item['code']]);
                        }
                        else {
                            $model->enabled = 1;
                        }

                        $model->code = $item['code'];
                        $model->name = $item['name'];
                        $model->phone = $item['phone'];
                        $model->homepage = $item['homepage'];

                        $model->save();

                        if ($fetchAll || $model->enabled)
                            $newList[] = $model;
                    }

                    //remove old no longer supported carriers.. should never happen o_0
                    foreach ($oldList as $model) {
                        $model->delete();
                    }

                    //update config
                    Mage::getModel('core/config')->saveConfig('pp_section_setttings/settings/last_carrier_update', time());

                    $carriers = $newList;
                }
            }
        }

        return $carriers;
    }

    /**
     * Get list of carriers with carrier code as key
     *
     * @param bool $fetchAll
     * @return array
     */
    public function getAssocList($fetchAll = false)
    {
        if (!$this->assocList) {
            $this->assocList = array();
            $list = $this->getList($fetchAll);

            foreach ($list as $item) {
                $this->assocList[$item->getCode()] = $item->getData('name');
            }
        }

        return $this->assocList;
    }

    /**
     * Get carrier code with prefix
     *
     * @return string
     */
    public function getPrefixedCode()
    {
        return self::CODE_PREFIX . $this->getCode();
    }

    public function getIconUrl()
    {
        return self::URL_ICONS . $this->getCode() . '.png';
    }

    /**
     * Get carrier code from pp_carriers string on detect from custom carrier title
     *
     * @param string $carrierCode
     * @param string|null $carrierTitle
     * @return bool|string
     */
    public function detectCarrier($carrierCode, $carrierTitle = null)
    {
        $carrierList = $this->getAssocList();

        $carrierCode = str_replace(Packpin_Pptrack_Model_Carrier::CODE_PREFIX, '', $carrierCode);
        $carrierCode2 = str_replace(Packpin_Pptrack_Model_Carrier::CODE_PREFIX, '', $carrierTitle);

        if (isset($carrierList[$carrierCode])) {
            return $carrierCode;
        } elseif (isset($carrierList[$carrierCode2])) {
            return $carrierCode2;
        } elseif (isset($carrierList[$carrierTitle])) {
            return $carrierTitle;
        }

        //try to identify by custom carrier code
        $regStr = implode('|', array_keys($carrierList));
        if (preg_match("#$regStr#ui", $carrierCode, $m)) {
            return $m[0];
        }

        //try to identify by carrier title
        if ($carrierTitle) {
            foreach ($carrierList as $code => $title) {
                if (stripos($carrierTitle, $title) !== false) {
                    return $code;
                }
            }
        }

        return false;
    }
}
