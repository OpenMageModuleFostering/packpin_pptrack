<?php

class Packpin_Pptrack_IndexController extends Mage_Core_Controller_Front_Action
{
    public function indexAction()
    {
        $model = Mage::getModel('pptrack/track');

        $hash = Mage::app()->getRequest()->getParam('h');
        if ($hash) {
            $model->loadInfoByHash($hash);
        }

        Mage::register('model', $model);

        $this->loadLayout();
        $this->getLayout()->getBlock("head")->setTitle(Mage::helper('pptrack')->__("Shipment status"));
        $breadcrumbs = $this->getLayout()->getBlock("breadcrumbs");
        $breadcrumbs->addCrumb("home", array(
            "label" => $this->__("Home"),
            "title" => $this->__("Home"),
            "link" => Mage::getBaseUrl()
        ));

        $breadcrumbs->addCrumb("shipment status", array(
            "label" => Mage::helper('pptrack')->__("Shipment status"),
            "title" => Mage::helper('pptrack')->__("Shipment status")
        ));

        //log user session
        try {
            $trackId = $model->getId();
            $ip = isset($_SERVER["REMOTE_ADDR"]) ? $_SERVER["REMOTE_ADDR"] : '';
            $agent = isset($_SERVER["HTTP_USER_AGENT"]) ? $_SERVER["HTTP_USER_AGENT"] : null;

            $userHash = md5($trackId . $ip . $agent);

            $model = Mage::getModel('pptrack/visit')
                ->getCollection()
                ->addFieldToFilter('user_hash', array('eq' => $userHash))
                ->addFieldToFilter("DATE_ADD(created_at, INTERVAL 1 DAY)", array('gteq' => date("Y-m-d H:i:s")))
//                ->addFieldToFilter("DATE_ADD(created_at, INTERVAL 1 DAY)", array('gteq' => new Zend_Db_Expr('NOW()')))
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


        $this->renderLayout();

    }

    public function popupAction()
    {
        $this->loadLayout();
        $this->getLayout()->getBlock("head")->setTitle(Mage::helper('pptrack')->__("Shipment status"));
        $this->renderLayout();
    }
}