<?php

class Packpin_Pptrack_UnsubscribeController extends Mage_Core_Controller_Front_Action
{
    public function indexAction(){

        $model = Mage::getModel('pptrack/track');
        $hash = Mage::app()->getRequest()->getParam('h');
        if ($hash) {
            $model->loadInfoByHash($hash);

            if($model->getId()) {
                $unsubscribedModel = Mage::getModel('pptrack/trackunsubscribed');
                $res = $unsubscribedModel->unsubscribeTrack($model->getId());
                if($res) {
                    Mage::getSingleton('core/session')->addSuccess(Mage::helper('pptrack')->__('You have successfully unsubscribed from package status updates'));
                }
                else {
                    Mage::getSingleton('core/session')->addWarning(Mage::helper('pptrack')->__('Subscription not found or already unsubscribed!'));
                }
            }
            else {
                Mage::getSingleton('core/session')->addError(Mage::helper('pptrack')->__('Tracking info not found!'));
            }
        }

        $this->loadLayout();
        $this->getLayout()->getBlock("head")->setTitle($this->__("Unsubscribe"));
        $this->renderLayout();
    }
}