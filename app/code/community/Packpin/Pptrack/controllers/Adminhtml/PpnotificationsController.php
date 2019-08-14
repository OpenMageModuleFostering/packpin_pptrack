<?php
class Packpin_Pptrack_Adminhtml_PpnotificationsController extends Mage_Adminhtml_Controller_Action
{
    protected function _initAction()
    {
        $this->loadLayout()
            ->_setActiveMenu('packpin/pptrack');

        return $this;
    }

    public function indexAction()
    {
        $this->_initAction()
            ->_title(Mage::helper('pptrack')->__("Packpin Notifications"))
            ->renderLayout();
    }

    /**
     * Notification setting page
     */
    public function settingsAction()
    {
        $this->_initAction()
            ->_title(Mage::helper('pptrack')->__("Packpin Notification Settings"))
            ->renderLayout();
    }

}