<?php

namespace MageSuite\DailyDeal\Model;

class OfferPriceIndexer extends \Magento\Catalog\Model\ResourceModel\Product\Indexer\AbstractIndexer
{
    protected function _construct()
    {
        $this->_init('catalog_product_index_price', 'entity_id');
    }

    public function addAttributeToSelect($select, $attrCode, $entity, $store) // phpcs:ignore
    {
        return $this->_addAttributeToSelect(
            $select,
            $attrCode,
            $entity,
            $store
        );
    }

    public function getAttribute($attributeCode)
    {
        return $this->_getAttribute($attributeCode);
    }
}
