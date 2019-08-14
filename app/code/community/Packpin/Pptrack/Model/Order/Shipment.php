<?php

class Packpin_Pptrack_Model_Order_Shipment extends Mage_Sales_Model_Order_Shipment {


    /**
     * Get all track and override tracking title to add tracking back link
     *
     * @return array
     */
    /*
    public function getAllTracks()
    {
        $tracks = parent::getAllTracks();

        $config = Mage::getStoreConfig('pp_section_setttings/settings');
        if (!$config['status'])
            return $tracks;

        //check if function called from email template
        //@FIXME maybe there is a better way to override this stuff o_0
        $callers = debug_backtrace();
        $args = $callers[1]['args'];
        if (count($args) && strstr($args[0], 'template/email/order/shipment/track.phtml')) {
            //override names
            $newItems = array();
            foreach ($tracks as $num => $item) {
                $newTitle = $item->getTitle() . ' (Whata fuck)';
                $item->title = $newTitle;
                $newItems[] = $item;
            }

            $tracks = $newItems;
        }

        return $tracks;
    }
    */

}
