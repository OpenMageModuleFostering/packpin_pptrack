<?php

class Packpin_Pptrack_Model_Adminhtml_System_Config_Source_Templatetype
{
    public function toOptionArray()
    {
        $options = array(
            array('value' => 'v1', 'label' => 'Template 1'),
            array('value' => 'v2', 'label' => 'Template 2'),
        );

        return $options;
    }
}