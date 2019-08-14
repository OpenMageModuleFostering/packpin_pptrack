<?php

class Packpin_Pptrack_IndexController extends Mage_Core_Controller_Front_Action
{
    public function indexAction()
    {
        if (isset($_REQUEST['PPTRACK_DEBUG'])) {
            restore_error_handler();
            error_reporting(E_ALL);
            ini_set("display_errors", "on");
        }


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


        $this->renderLayout();

    }

    public function popupAction()
    {
        $this->loadLayout();
        $this->getLayout()->getBlock("head")->setTitle(Mage::helper('pptrack')->__("Shipment status"));
        $this->renderLayout();
    }
}