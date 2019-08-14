<?php
class Packpin_Pptrack_Adminhtml_PackpintracksController extends Mage_Adminhtml_Controller_Action
{
    protected function _initAction()
    {
        $this->loadLayout()
            ->_setActiveMenu('dashboard/pptrack');

        return $this;
    }

    public function dashboardAction()
    {
        $this->_initAction()
            ->_title($this->__('Dashboard'))->_title($this->__('Packpin'))
            ->_addBreadcrumb($this->__('Dashboard'), $this->__('Dashboard'))
            ->_addBreadcrumb($this->__('pptrack'), $this->__('Packpin'))
            ->renderLayout();
    }



}