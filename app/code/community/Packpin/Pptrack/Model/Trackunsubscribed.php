<?php

/**
 * Class Packpin_Pptrack_Model_Trackunsubscribed
 *
 * Model for pp_tracks_unsubscribed table
 */
class Packpin_Pptrack_Model_Trackunsubscribed extends Mage_Core_Model_Abstract
{

    protected function _construct()
    {
        $this->_init('pptrack/trackunsubscribed');
    }

    public function unsubscribeTrack($trackId)
    {
        $this->load($trackId, 'track_id');

        if ($this->getId()) {
            return false;
        }
        else {
            $this->setTrackId($trackId);
            $this->save();

            return true;
        }
    }

}
