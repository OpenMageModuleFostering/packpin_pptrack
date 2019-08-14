<?php
class Packpin_Pptrack_Block_Adminhtml_Tracks extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    public function __construct()
    {
        // The blockGroup must match the first half of how we call the block, and controller matches the second half
        // ie. foo_bar/adminhtml_baz
        $this->_blockGroup = 'pptrack';
        $this->_controller = 'adminhtml_tracks';
        $this->_headerText = $this->__('Tracked shipments');

        parent::__construct();
        $this->_removeButton('add');
    }
}