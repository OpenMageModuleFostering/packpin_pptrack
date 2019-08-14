<?php

class Packpin_Pptrack_Model_Api extends Mage_Api_Model_Resource_Abstract
{

    const ERROR_EXTENSION_DISABLED = 'extension_disabled';
    const ERROR_NOTIFICATIONS_DISABLED = 'notifications_disabled';
    const ERROR_NO_TEMPLATE = 'no_template';

    const DEFAULT_LOCALE = 'en_US';

    /**
     * Get email info
     *
     * @param string $carrierCode
     * @param string $trackingCode
     *
     * @return array
     */
    public function info($carrierCode, $trackingCode, $status)
    {
        //extension disabled
        $enabled = Mage::getStoreConfig('pp_section_setttings/settings/status');
        if (!$enabled)
            return array(
                'error' => self::ERROR_EXTENSION_DISABLED
            );

        //notifications disabled
        $enabled = Mage::getStoreConfig('pp_section_setttings/settings/pp_enable_notifications');
        if (!$enabled)
            return false;

        $trackModel = Mage::getModel('pptrack/track')
            ->loadByCarrierAndCode($carrierCode, $trackingCode);

        //maybe changed to custom by some API's (for older versions)
        if (!$trackModel->getId()) {
            $trackModel = Mage::getModel('pptrack/track')
                ->loadByCarrierAndCode('custom', $trackingCode);
        }

        if ($trackModel->getId()) {
            $order = Mage::getModel('sales/order')->load($trackModel->getOrderId());
            $store = $order->getStore();
            $storeId = $store->getId();

            $templateSettings = Mage::getStoreConfig('pp_section_notification_emails/' . $status, $storeId);
            if (!$templateSettings || (!$templateSettings['enabled_owner'] && !$templateSettings['enabled_client']))
                return self::ERROR_NOTIFICATIONS_DISABLED;

            $data = array();

            //sender data
            $data['sender_name'] = Mage::getStoreConfig('trans_email/ident_'. $templateSettings['identity'] .'/name');
            $data['sender_email'] = Mage::getStoreConfig('trans_email/ident_'. $templateSettings['identity'] .'/email');

            //shop data
            if ($templateSettings['enabled_owner']) {
                $data['owner_name'] = Mage::getStoreConfig('trans_email/ident_general/name');
                $data['owner_email'] = Mage::getStoreConfig('trans_email/ident_general/email');
            }

            //client data
            //check if client unsubscribed
            try {
                $unsubscribed = Mage::getModel('pptrack/trackunsubscribed')
                    ->load($trackModel->getId(), 'track_id');
            }
            catch (Exception $e) {
                $unsubscribed = Mage::getModel('pptrack/trackunsubscribed');
            }

            if (!$unsubscribed->getId() && $templateSettings['enabled_client']) {
                $data['client_name'] = $order->getCustomerName();
                $data['client_email'] = $order->getCustomerEmail();
            }

            //email template data
            $templateId = $templateSettings['template'];

            Mage::getDesign()->setStore($store);
            $emailTemplate  = Mage::getModel('core/email_template');
            $emailTemplate->emulateDesign($storeId);
            if (is_numeric($templateId)) {
                $emailTemplate->load($templateId);
            } else {
                $templateId = 'packpin_'. $status .'_email';

//                $storeId = $order->getStore()->getId();
//                $localeCode = Mage::getStoreConfig('general/locale/code', $storeId);
                $localeCode = self::DEFAULT_LOCALE;

                $emailTemplate->loadDefault($templateId, $localeCode);
            }

            $crossSell = '';
            $banner = '';
            if (Mage::getStoreConfig('pp_section_setttings/crosssell/cross_sell_email')) {
                if (in_array(Mage::getStoreConfig('pp_section_setttings/crosssell/cross_sell_email_type'), array('ads'))) {
                    $banner = Mage::app()->getLayout()->createBlock('pptrack/ads')->setData()->setTemplate('pptrack/ads_email.phtml')->toHtml();
                }
                else {
                    $crossSell = Mage::app()->getLayout()->createBlock('pptrack/crosssell')->setData(array('orderId' => $trackModel->getOrderId()))->setTemplate('pptrack/crosssell_email.phtml')->toHtml();
                }
            }

            $variables = array(
                'email' => $order->getCustomerEmail(),
                'track' => $trackModel,
                'crossSell' => $crossSell,
                'banner' => $banner,
            );

            $processedTemplate = $emailTemplate->getProcessedTemplate($variables);
            if (!$processedTemplate)
                return array(
                    'error' => self::ERROR_NO_TEMPLATE
                );


            $data['email_subject'] = $emailTemplate->getProcessedTemplateSubject($variables);
            $data['email_body'] = $processedTemplate;

            return $data;
        }

        return false;
    }

    public function test()
    {
        return array(
            'status' => 1
        );
    }

}