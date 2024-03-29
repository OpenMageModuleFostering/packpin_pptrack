<?php
class Packpin_Pptrack_Block_Ads extends Mage_Catalog_Block_Product_Abstract
{
    public function getImage()
    {
        $image = Mage::getStoreConfig('pp_section_setttings/crosssell/cross_sell_page_image');

        if ($image) {
            return Mage::getBaseUrl('media') . 'theme' . DIRECTORY_SEPARATOR . $image;
        }

        return false;
    }

    public function getEmailImage()
    {
        $image = Mage::getStoreConfig('pp_section_setttings/crosssell/cross_sell_email_image');

        if ($image) {
            return Mage::getBaseUrl('media') . 'theme' . DIRECTORY_SEPARATOR . $image;
        }

        return false;
    }

    public function getImageUrl()
    {
        $url = Mage::getStoreConfig('pp_section_setttings/crosssell/cross_sell_page_image_url');

        return $url;
    }

    public function getEmailImageUrl()
    {
        $url = Mage::getStoreConfig('pp_section_setttings/crosssell/cross_sell_email_image_url');

        return $url;
    }
}