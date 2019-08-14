<?php

class Packpin_Pptrack_Model_Resource_Track extends Mage_Core_Model_Mysql4_Abstract
{
    protected function _construct()
    {
        $this->_init('pptrack/track', 'id');
    }

}