<?php

namespace MageSuite\DailyDeal\Observer;

class UpdateProductFinalPrice implements \Magento\Framework\Event\ObserverInterface
{
    protected \MageSuite\DailyDeal\Helper\Configuration $configuration;

    protected bool $finalPriceColumnExists = true;
    protected \Magento\Framework\EntityManager\MetadataPool $metadataPool;
    protected \MageSuite\DailyDeal\Model\OfferPriceIndexer $offerPriceIndexer;

    /**
     * @var ?/Zend_Db_Expr
     */
    protected $storeField;

    public function __construct(
        \MageSuite\DailyDeal\Helper\Configuration $configuration,
        \Magento\Framework\EntityManager\MetadataPool $metadataPool,
        \MageSuite\DailyDeal\Model\OfferPriceIndexer $offerPriceIndexer
    ) {
        $this->configuration = $configuration;
        $this->metadataPool = $metadataPool;
        $this->offerPriceIndexer = $offerPriceIndexer;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if (!$this->configuration->isActive()) {
            return $this;
        }

        $select = $observer->getEvent()->getSelect();

        $this->applySqlFields($observer->getEvent()->getData());

        $connection = $this->offerPriceIndexer->getConnection();

        $finalPrice = $this->getFinalPrice($connection, $select);

        if (!$finalPrice) {
            return $this;
        }

        $this->applyFinalPriceToSelect($connection, $select, $finalPrice);

        return $this;
    }

    private function applySqlFields($eventData)
    {
        $this->storeField = $eventData['store_field'];
    }

    protected function getFinalPrice($connection, $select)
    {
        $priceColumn = $this->getPriceColumn($select);

        if (!$priceColumn) {
            return false;
        }

        $dailyDealColumns = $this->prepareDailyDealColumns($select);

        $finalPrice = $connection->getCheckSql(
            "{$dailyDealColumns['is_enabled']} = 1" . " AND {$dailyDealColumns['price']} < {$priceColumn}",
            $dailyDealColumns['price'],
            $priceColumn
        );

        return $finalPrice;
    }

    protected function prepareDailyDealColumns($select)
    {
        $linkField = sprintf(
            'e.%s',
            $this->metadataPool->getMetadata(\Magento\Catalog\Api\Data\ProductInterface::class)->getLinkField()
        );

        $columnExist = $this->ensureColumnExistsInSelect($select, 'e.entity_id');

        if (!$columnExist) {
            return [];
        }

        $result['price'] = $this->offerPriceIndexer->addAttributeToSelect(
            $select,
            'daily_deal_price',
            $linkField,
            $this->storeField
        );

        $result['is_enabled'] = $this->offerPriceIndexer->addAttributeToSelect(
            $select,
            'daily_deal_enabled',
            $linkField,
            $this->storeField
        );

        return $result;
    }

    protected function ensureColumnExistsInSelect($select, $entityField)
    {
        $columnExist = false;

        foreach ($select->getPart(\Magento\Framework\DB\Select::COLUMNS) as $columnEntry) {
            list($correlationName, $column, $alias) = $columnEntry;

            $fullColumnName = $correlationName . '.' . $column;

            if ($fullColumnName == $entityField) {
                $columnExist = true;
            }
        }

        return $columnExist;
    }

    protected function getPriceColumn($select)
    {
        $priceColumn = null;

        foreach ($select->getPart(\Magento\Framework\DB\Select::COLUMNS) as $columnEntry) {
            list($correlationName, $column, $alias) = $columnEntry;

            if ($alias == 'final_price') {
                return $column;
            }

            if ($alias == 'price') {
                $priceColumn = $column;
            }
        }

        if ($priceColumn) {
            $this->finalPriceColumnExists = false;
        }

        return $priceColumn;
    }

    protected function applyFinalPriceToSelect($connection, $select, $finalPrice)
    {
        $columns = [];

        $columnFields = ['min_price', 'max_price', 'final_price'];

        if (!$this->finalPriceColumnExists) {
            $columnFields[] ='price';
        }

        foreach ($select->getPart(\Magento\Framework\DB\Select::COLUMNS) as $columnEntry) {
            list($correlationName, $column, $alias) = $columnEntry;

            if (in_array($alias, $columnFields)) {
                $column = $connection->getIfNullSql($finalPrice, 0);
            }

            $columns[] = [$correlationName, $column, $alias];
        }

        $select->setPart(\Magento\Framework\DB\Select::COLUMNS, $columns);
    }
}
