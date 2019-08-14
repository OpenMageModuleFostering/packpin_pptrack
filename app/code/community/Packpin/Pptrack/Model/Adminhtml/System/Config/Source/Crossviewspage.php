<?php

class Packpin_Pptrack_Model_Adminhtml_System_Config_Source_Crossviewspage
{
    public function toOptionArray()
    {
        $options = array(
            array('value' => 'products', 'label' => 'Assigned cross-sell products'),
            array('value' => 'ads', 'label' => 'Ads banner'),
            array('value' => 'script', 'label' => 'Custom script'),
        );

        return $options;
    }
}