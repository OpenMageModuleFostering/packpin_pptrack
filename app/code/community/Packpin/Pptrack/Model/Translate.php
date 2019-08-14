<?php

/**
 * Class Packpin_Pptrack_Model_Translate
 * Temporary override email processing to include back link to shop
 *
 * Used for dirty shipment back link injection
 * Hope temporary o_0
 */
class Packpin_Pptrack_Model_Translate extends Mage_Core_Model_Translate
{
    /**
     * Retrieve translated template file
     *
     * @param string $file
     * @param string $type
     * @param string $localeCode
     * @return string
     */
    public function getTemplateFile($file, $type, $localeCode=null)
    {
        $fileContent = parent::getTemplateFile($file, $type, $localeCode);

        $config = Mage::getStoreConfig('pp_section_setttings/settings');
        if (!$config['status'])
            return $fileContent;

        if (in_array($file, array('sales/shipment_new.html', 'sales/shipment_new_guest.html'))) {
            $newBlock = '{{block type=\'pptrack/trackings\' area=\'frontend\' template=\'pptrack/email/order/shipment/track.phtml\' shipment=$shipment order=$order}}';

            //replace trackings table if found
            if (preg_match('#{{[^}]+track\.phtml[^}]+}}#ui', $fileContent, $m)) {
                $fileContent = preg_replace('#{{[^}]+track\.phtml[^}]+}}#ui', $newBlock, $fileContent);
            }
        }

        return $fileContent;
    }
}