<?php

/**
 * Class Packpin_Pptrack_Helper_Data
 *
 * API calls helpers
 * see https://packpin.com/docs for more documentation
 */
class Packpin_Pptrack_Helper_Data extends Mage_Core_Helper_Abstract
{
    /**
     * Backend
     */
    const API_BACKEND = 'https://api.packpin.com/v2/';

    /**
     * Api routes
     */
    const API_PATH_CARRIERS = 'carriers';
    const API_PATH_TRACKINGS = 'trackings';
    const API_PATH_TRACKINGS_BATCH = 'trackings/batch';
    const API_PATH_TRACKING_INFO = 'trackings/%s/%s';
    const API_PATH_CONNECTORS = 'connectors';
    const API_PATH_TEST = 'test/1';
    const API_PATH_GENERATE_TEMP_KEY = 'genkey';
    const API_PATH_PLAN_INFO = 'planinfo';
    const API_PATH_EMAIL_COUNT = 'emails/count';


    const API_ROLE_NAME = 'packpin_connection';

    /**
     * Config paths
     */
    const XML_PATH_API_KEY = 'pp_section_setttings/settings/api_key';

    /**
     * Packpin API key
     *
     * @var string
     */
    protected $_apiKey;

    /**
     * Last API call status code
     *
     * @var integer
     */
    protected $_lastStatusCode;

    protected function _getApiKey()
    {
        if ($this->_apiKey === null) {
            $this->_apiKey = Mage::getStoreConfig(self::XML_PATH_API_KEY);
        }

        return $this->_apiKey;
    }

    /**
     * Make API request
     *
     * @param string $route
     * @param string $method
     * @param array $body
     *
     * @return bool|array
     */
    protected function _apiRequest($route, $method = 'GET', $body = array())
    {
        $store = Mage::app()->getStore();
        $body['plugin_type'] = 'magento';
        $body['plugin_version'] = $this->getExtensionVersion();
        $body['plugin_shop_version'] = Mage::getVersion();
        $body['plugin_user'] = Mage::getStoreConfig('trans_email/ident_general/name');
        $body['plugin_email'] = Mage::getStoreConfig('trans_email/ident_general/email');
        $body['plugin_url'] = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB);

        $url = self::API_BACKEND . $route;

        $ch = curl_init($url);
        if ($method == 'POST') {
            curl_setopt($ch, CURLOPT_POST, 1);
        } elseif ($method == 'PUT') {
            curl_setopt($ch, CURLOPT_PUT, true);
        } elseif ($method != 'PUT') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        }
        //timeouts
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 25);
        curl_setopt($ch, CURLOPT_TIMEOUT, 90);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        $headers = array(
            'packpin-api-key: ' . $this->_getApiKey(),
            'Content-Type: application/json',
        );
        if ($body) {
            $dataString = json_encode($body);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $dataString);
            $headers[] = 'Content-Length: ' . strlen($dataString);
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $response = curl_exec($ch);
        $this->_lastStatusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        unset($ch);

        return $response;
    }

    /**
     * Get info about single tracking object
     *
     * @param string $carrierCode
     * @param string $trackingCode
     *
     * @return array
     */
    public function getTrackingInfo($carrierCode, $trackingCode)
    {
        $info = array();

        $url = sprintf(self::API_PATH_TRACKING_INFO, $carrierCode, $trackingCode);

        $res = $this->_apiRequest($url, 'GET');
        if ($res) {
            $info = json_decode($res, true);
        }

        return $info;
    }

    /**
     * Get list of available carriers
     *
     * @return array
     */
    public function getCarrierList()
    {
        $info = array();

        $url = self::API_PATH_CARRIERS;

        $res = $this->_apiRequest($url, 'GET');
        if ($res) {
            $info = json_decode($res, true);
        }

        return $info;
    }

    /**
     * Add new tracking code
     *
     * @param string $carrierCode
     * @param string $trackingCode
     * @param string|null $description
     * @param string|null $postalCode
     * @param string|null $destinationCountry
     * @param string|null $shipDate
     * @param integer|null $orderId
     *
     * @return array
     */
    public function addTrackingCode($carrierCode, $trackingCode, $description = null, $postalCode = null, $destinationCountry = null, $shipDate = null, $orderId = null)
    {
        $info = array();

        $url = self::API_PATH_TRACKINGS;
        $body = array(
            'code' => $trackingCode,
            'carrier' => $carrierCode,
            'description' => $description,
            'track_postal_code' => $postalCode,
            'track_ship_date' => $shipDate,
            'track_destination_country' => $destinationCountry,
            'order_id' => $orderId,
        );

        $res = $this->_apiRequest($url, 'POST', $body);
        if ($res) {
            $info = json_decode($res, true);
        }

        return $info;
    }

    public function removeTrackingCode($carrierCode, $trackingCode)
    {
        $info = array();

        $url = sprintf(self::API_PATH_TRACKING_INFO, $carrierCode, $trackingCode);

        $res = $this->_apiRequest($url, 'DELETE');

        if ($res) {
            $info = json_decode($res, true);
        } else {
            $info = array(
                "statusCode" => $this->_lastStatusCode
            );
        }

        return $info;
    }

    public function enableConnector($status = 0)
    {
        $info = array();

        $url = self::API_PATH_CONNECTORS;
        $body = array(
            'path' => Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB),
            'enabled' => $status,
        );

        $res = $this->_apiRequest($url, 'POST', $body);

        if ($res) {
            $info = json_decode($res, true);
        }

        if (!$info && preg_match('#SOAP-ERROR#ui', $res, $m)) {
            $info = array(
                'statusCode' => 400,
                'body' => array(
                    'reason' => 'Could not connect to Magento shop API'
                ),
            );
        }

        return $info;
    }

    public function testApiKey()
    {
        $info = array();

        $url = self::API_PATH_TEST;
        $res = $this->_apiRequest($url, 'GET');
        if ($res) {
            $info = json_decode($res, true);
        }

        return $info;
    }

    public function createSoapUserAndRole($apiKey)
    {

        $connection = Mage::getSingleton('core/resource')->getConnection('core_write');
        try {
            $connection->beginTransaction();

            $user = Mage::getModel('api/user')->loadByUsername("packpin");
            $user->setUsername('packpin')->setFirstname('packpin')->setLastname('packpin')->setEmail('info@packpin.com')->setApiKey($apiKey)->setApiKeyConfirmation($apiKey)->setIsActive(1)->save();

            $role = Mage::getModel('api/roles')->load(self::API_ROLE_NAME, "role_name");
            if (!$role->getId()) {
                $res = Mage::getModel('api/role')->setParentId('0')->setTreeLevel('1')->setSortOrder('0')->setRoleName(self::API_ROLE_NAME)->setRoleType('G')->save();
                $rules = Mage::getModel('api/rules')->setRoleId($res->getRoleId())->setResources(array("pptrack", "pptrack/info", "pptrack/test"));
                //for newer magento versions
                $rrModel = Mage::getModel('api/resource_rules');
                if ($rrModel) {
                    $rrModel->saveRel($rules);
                }
                //Mage 1.5
                else {
                    $rules->saveRel();
                }

                $role = $res;
            }

            $user->setRoleIds(array($role->getId()))
                ->setRoleUserId($user->getUserId())
                ->saveRelations();


            ////Stupid hack. Should be fixed properly in the future
            $transactionLevel = $connection->getTransactionLevel();
            for ($i = 1; $i <= $transactionLevel; $i++) {
                $connection->commit();
            }

        } catch (Exception $e) {
            $connection->rollback();
        }

        return true;
    }

    public function getExtensionVersion()
    {
        return (string) Mage::getConfig()->getNode()->modules->Packpin_Pptrack->version;
    }

    /**
     * Generates temporary API key for fresh plugin install
     *
     * @return string
     */
    public function generateTempKey()
    {
        $url = self::API_PATH_GENERATE_TEMP_KEY;
        $body = array(
            'name' => Mage::getStoreConfig('trans_email/ident_general/name'),
            'email' => Mage::getStoreConfig('trans_email/ident_general/email'),
        );

        $res = $this->_apiRequest($url, 'POST', $body);
        if ($res) {
            $info = json_decode($res, true);
            if (isset($info['body']['key']))
                return $info['body']['key'];
        }

        return false;
    }

    /**
     * Get details about current plan
     *
     * @return array
     */
    public function getPlanDetails()
    {
        $info = array();
        $url = self::API_PATH_PLAN_INFO;

        $res = $this->_apiRequest($url, 'GET');
        if ($res) {
            $info = json_decode($res, true);
        }

        return $info;
    }

    /**
     * Get email sent count for period
     *
     * @param string $start
     * @param string $end
     *
     * @return array
     */
    public function getEmailCount($start, $end)
    {
        $info = array();
        $url = self::API_PATH_EMAIL_COUNT;
        $body = array(
            'start' => $start,
            'end' => $end,
        );

        $res = $this->_apiRequest($url, 'POST', $body);
        if ($res) {
            $info = json_decode($res, true);
        }

        return $info;
    }

}
	 