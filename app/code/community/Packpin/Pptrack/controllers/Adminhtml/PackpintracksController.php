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

    public function indexAction()
    {
        $this->_initAction()
            ->_title($this->__('Dashboard'))->_title($this->__('Packpin'))
            ->_addBreadcrumb($this->__('Dashboard'), $this->__('Dashboard'))
            ->_addBreadcrumb($this->__('pptrack'), $this->__('Packpin'))
            ->renderLayout();
    }

    public function newAction()
    {
        // We just forward the new action to a blank edit form
        $this->_forward('edit');
    }

    public function editAction()
    {
        $this->_initAction();

        // Get id if available
        $id  = $this->getRequest()->getParam('id');
        $model = Mage::getModel('pptrack/track');

        if ($id) {
            // Load record
            $model->load($id);

            // Check if record is loaded
            if (!$model->getId()) {
                Mage::getSingleton('adminhtml/session')->addError($this->__('This Track no longer exists.'));
                $this->_redirect('*/*/');

                return;
            }
        }

        $this->_title($model->getId() ? $model->getName() : $this->__('New Track'));

        $data = Mage::getSingleton('adminhtml/session')->getTracksData(true);
        if (!empty($data)) {
            $model->setData($data);
        }

        Mage::register('pptrack', $model);

        $this->_initAction()
            ->_addBreadcrumb($id ? $this->__('Edit Track') : $this->__('New Track'), $id ? $this->__('Edit Track') : $this->__('New Track'))
            ->_addContent($this->getLayout()->createBlock('pptrack/adminhtml_tracks_edit')->setData('action', $this->getUrl('*/*/save')))
            ->renderLayout();
    }

    public function saveAction()
    {
        if ($postData = $this->getRequest()->getPost()) {
            $model = Mage::getSingleton('pptrack/Track');
            $model->setData($postData);

            try {
                $model->save();

                Mage::getSingleton('adminhtml/session')->addSuccess($this->__('The Track has been saved.'));
                $this->_redirect('*/*/');

                return;
            }
            catch (Mage_Core_Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            }
            catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($this->__('An error occurred while saving this track.'));
            }

            Mage::getSingleton('adminhtml/session')->setBazData($postData);
            $this->_redirectReferer();
        }
    }

    public function messageAction()
    {
        $data = Mage::getModel('pptrack/Track')->load($this->getRequest()->getParam('id'));
        echo $data->getContent();
    }

    public function exportTracksCsvAction()
    {
        $fileName = 'tracks.csv';
        $grid = $this->getLayout()->createBlock('pptrack/adminhtml_tracks_grid');
        $this->_prepareDownloadResponse($fileName, $grid->getCsvFile());
    }

    public function exportTracksExcelAction()
    {
        $fileName = 'tracks.xml';
        $grid = $this->getLayout()->createBlock('pptrack/adminhtml_tracks_grid');
        $this->_prepareDownloadResponse($fileName, $grid->getExcelFile($fileName));
    }

}