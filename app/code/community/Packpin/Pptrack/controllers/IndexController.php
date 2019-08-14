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
        $this->getLayout()->getBlock("head")->setTitle($this->__("Shipment status"));
        $breadcrumbs = $this->getLayout()->getBlock("breadcrumbs");
        $breadcrumbs->addCrumb("home", array(
            "label" => $this->__("Home Page"),
            "title" => $this->__("Home Page"),
            "link" => Mage::getBaseUrl()
        ));

        $breadcrumbs->addCrumb("shipment status", array(
            "label" => $this->__("Shipment status"),
            "title" => $this->__("Shipment status")
        ));

        $this->renderLayout();

    }

    public function popupAction()
    {
        $this->loadLayout();
        $this->getLayout()->getBlock("head")->setTitle($this->__("Shipment status"));
        $this->renderLayout();
    }
}