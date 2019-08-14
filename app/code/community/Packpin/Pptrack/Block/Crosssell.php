<?php
class Packpin_Pptrack_Block_Crosssell extends Mage_Catalog_Block_Product_Abstract
{
    public function getCrossSellingItemsByOrderId($orderId, $limit)
    {
        $crossSellingItemsArray = array();
        $orders = Mage::getModel('sales/order')->load($orderId);
        $items = $orders->getAllVisibleItems();

        foreach ($items as $item) {
            $product = Mage::getModel('catalog/product')->load($item->getProductId());
            $crossSellCollection = $product->getCrossSellProductCollection();
            $crossSellCollection->getSelect()->order(new Zend_Db_Expr('RAND()'))->limit($limit);
//            $crossSellCollection->setPage(1, 5);
            foreach ($crossSellCollection as $crossSellItem) {
                $crossSellingItemsArray[] = Mage::getModel('catalog/product')->load($crossSellItem->getId());
            }
        }
        return $crossSellingItemsArray;
    }

}