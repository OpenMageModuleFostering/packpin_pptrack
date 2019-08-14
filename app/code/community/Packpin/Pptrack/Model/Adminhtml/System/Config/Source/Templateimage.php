<?php

class Packpin_Pptrack_Model_Adminhtml_System_Config_Source_Templateimage
{
    public function toOptionArray()
    {
        $options = array(
            array('value' => 'default', 'label' => 'Default template image'),
            array('value' => 'custom', 'label' => 'Custom image'),
        );

        return $options;
    }
}