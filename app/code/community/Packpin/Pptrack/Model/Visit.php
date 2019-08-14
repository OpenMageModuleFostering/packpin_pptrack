<?php

/**
 * Class Packpin_Pptrack_Model_Visit
 *
 * Model for pp_visits table
 */
class Packpin_Pptrack_Model_Visit extends Mage_Core_Model_Abstract
{

    protected function _construct()
    {
        $this->_init('pptrack/visit');
    }

    public function getTotalCount()
    {
        $count = $this->getCollection()
            ->getSize();

        return $count;
    }

}
