<?php

class Packpin_Pptrack_Block_Adminhtml_Dashboard extends Mage_Adminhtml_Block_Dashboard_Abstract
{

    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('pptrack/dashboard/packpin.phtml');
    }

    public function getTotalShipments()
    {
        $count = Mage::getModel('pptrack/track')
            ->getTotalCount();

        return $count;
    }

    public function getTotalVisits()
    {
        $count = Mage::getModel('pptrack/visit')
            ->getTotalCount();

        return number_format($count);
    }

    public function getPlanDetails()
    {
        $helper = Mage::helper('pptrack');
        $res = $helper->getPlanDetails();

        //map new data to current model
        if ($res && $res["statusCode"] == 200) {
            $info = $res["body"];

            return $info;
        }

        return array();
    }

    /**
     * Get info for range selector
     */
    public function getRangeInfo()
    {
        $now = date("Y-m-d");
        $date = new DateTime();
        $d30 = $date->modify("-30 day")->format("Y-m-d");
        $date = new DateTime();
        $d1 = $date->modify("-1 day")->format("Y-m-d");
        $date = new DateTime();
        $d7 = $date->modify("-7 day")->format("Y-m-d");

        $dateStart = Mage::app()->getRequest()->getParam('date_from');
        $dateEnd = Mage::app()->getRequest()->getParam('date_to');


        $rangeInfo = array(
            'last30days' => array(
                'label' => Mage::helper('pptrack')->__('Last 30 days'),
                'date_start' => $d30,
                'date_end' => $now,
            ),
            'last7days' => array(
                'label' => Mage::helper('pptrack')->__('Last 7 days'),
                'date_start' => $d7,
                'date_end' => $now,
            ),
            'yesterday' => array(
                'label' => Mage::helper('pptrack')->__('Yesterday'),
                'date_start' => $d1,
                'date_end' => $d1,
            ),
            'today' => array(
                'label' => Mage::helper('pptrack')->__('Today'),
                'date_start' => $now,
                'date_end' => $now,
            ),
            'custom' => array(
                'label' => Mage::helper('pptrack')->__('Custom'),
                'date_start' => $dateStart,
                'date_end' => $dateEnd,
            ),
        );

        return $rangeInfo;
    }

    public function getSelectedRange()
    {
        $ranges = $this->getRangeInfo();
        $range = Mage::app()->getRequest()->getParam('range', key($ranges));

        return $range;
    }

    /**
     * @return array
     */
    public function getRangeStats()
    {
        $stats = array(
            'trackings' => 0,
            'emails' => '?',
            'visits' => 0,
            'average' => 0,
            'graph' => array(),
        );

        $rangeInfo = $this->getRangeInfo();
        $range = $this->getSelectedRange();

        $start = $rangeInfo[$range]['date_start'];
        $startTime = strtotime($start . ' 00:00:00');
        $end = $rangeInfo[$range]['date_end'];
        $endTime = strtotime($end . ' 23:59:59');

        $stats['trackings'] = Mage::getModel('pptrack/track')
            ->getCollection()
            ->addFieldToFilter("created_at", array('gteq' => $startTime))
            ->addFieldToFilter("created_at", array('lteq' => $endTime))
            ->getSize();

        $stats['visits'] = Mage::getModel('pptrack/visit')
            ->getCollection()
            ->addFieldToFilter("DATE(created_at)", array('gteq' => $start))
            ->addFieldToFilter("DATE(created_at)", array('lteq' => $end))
            ->getSize();

        $stats['average'] = $stats['trackings'] ? $stats['visits'] / $stats['trackings'] : 0;

        //email stats
        $helper = Mage::helper('pptrack');
        $res = $helper->getEmailCount($start, $end);

        //map new data to current model
        if ($res && $res["statusCode"] == 200) {
            $stats['emails'] = $res["body"]['count'];
        }

        //graph data
        $data = array();
        $list1 = array();
        $list2 = array();
        $collection = Mage::getModel('pptrack/track')
            ->getCollection()
            ->addFieldToFilter("created_at", array('gteq' => $startTime))
            ->addFieldToFilter("created_at", array('lteq' => $endTime));
        $collection->getSelect()
            ->group(new Zend_Db_Expr('DATE(FROM_UNIXTIME(created_at))'))
            ->columns(new Zend_Db_Expr('DATE(FROM_UNIXTIME(created_at)) as date, COUNT(*) as count'));
        $list1Raw = $collection->getData();

        $collection = Mage::getModel('pptrack/visit')
            ->getCollection()
            ->addFieldToFilter("DATE(created_at)", array('gteq' => $start))
            ->addFieldToFilter("DATE(created_at)", array('lteq' => $end));
        $collection->getSelect()
            ->group(new Zend_Db_Expr('DATE(created_at)'))
            ->columns(new Zend_Db_Expr('DATE(created_at) as date, COUNT(*) as count'));
        $list2Raw = $collection->getData();

        foreach ($list1Raw as $item) {
            $list1[$item['date']] = $item['count'];
        }
        foreach ($list2Raw as $item) {
            $list2[$item['date']] = $item['count'];
        }

        $startObj = DateTime::createFromFormat("Y-m-d", $start);
        $endObj = DateTime::createFromFormat("Y-m-d", $end);
        $ct = 0;
        try {
            do {
                $date = $startObj->format('Y-m-d');

                $count = isset($list1[$date]) ? $list1[$date] : 0;
                $count2 = isset($list2[$date]) ? $list2[$date] : 0;

                $data[$date] = array(
                    'period' => $date,
                    'count' => $count,
                    'count2' => $count2,
                );


                $startObj->modify('1 day');
                $ct++;
            } while ($startObj <= $endObj && $ct < 5000);
        } catch (Exception $e) {

        }

        $stats['graph'] = $data;

        return $stats;
    }

    public function pluginEnabled()
    {
        return Mage::getStoreConfig('pp_section_setttings/settings/status');
    }

    public function notificationsEnabled()
    {
        return Mage::getStoreConfig('pp_section_setttings/settings/pp_enable_notifications');
    }

}