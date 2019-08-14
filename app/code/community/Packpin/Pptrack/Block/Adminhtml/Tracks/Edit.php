<?php
class Packpin_Pptrack_Block_Adminhtml_Tracks_Edit extends Mage_Adminhtml_Block_Widget_Form_Container
{
    /**
     * Init class
     */
    public function __construct()
    {
        $this->_blockGroup = 'pptrack';
        $this->_controller = 'adminhtml_tracks';

        parent::__construct();

        $this->_updateButton('save', 'label', $this->__('Save Track'));
        $this->_updateButton('delete', 'label', $this->__('Delete Track'));
        $this->_updateButton('back', 'onclick', 'setLocation(\'' . $this->getUrl('*/*/dashboard/') . '\')');
    }

    /**
     * Get Header text
     *
     * @return string
     */
    public function getHeaderText()
    {
        if (Mage::registry('pptrack')->getId()) {
            return $this->__('Edit Track');
        }
        else {
            return $this->__('New Track');
        }
    }
}