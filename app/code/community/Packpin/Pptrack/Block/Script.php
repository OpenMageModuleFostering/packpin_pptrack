<?php
class Packpin_Pptrack_Block_Script extends Mage_Catalog_Block_Product_Abstract
{
    public function getScript()
    {
        return Mage::getStoreConfig('pp_section_setttings/crosssell/cross_sell_page_script');
    }

}