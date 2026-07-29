<?php

namespace MageSuite\DailyDeal\Model\ResourceModel;

class Offer extends \Magento\Catalog\Model\ResourceModel\AbstractResource
{
    const DEFAULT_STORE_ID = 0;

    protected array $attributes = [];

    protected \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory;

    protected \Magento\Framework\App\ResourceConnection $resource;

    protected \Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable $configurableModel;

    protected \Magento\Catalog\Model\ProductRepository $productRepository;

    protected \MageSuite\DailyDeal\Helper\Configuration $configuration;

    public function __construct(
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
        \Magento\Framework\App\ResourceConnection $resource,
        \Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable $configurableModel,
        \Magento\Catalog\Model\ProductRepository $productRepository,
        \MageSuite\DailyDeal\Helper\Configuration $configuration
    ) {
        $this->productCollectionFactory = $productCollectionFactory;
        $this->resource = $resource;
        $this->configurableModel = $configurableModel;
        $this->productRepository = $productRepository;
        $this->configuration = $configuration;
    }

    public function getOffersByParameters($timestamp, $storeId)
    {
        $currentDateFilter = date('Y-m-d H:i:s', $timestamp);

        $enabledOffers = $this->getEnabledOffers($storeId);
        $pendingOffers = $this->getPendingOffers($currentDateFilter, $storeId);

        $offers = $enabledOffers + $pendingOffers;
        ksort($offers, SORT_NUMERIC);

        return $offers;
    }

    protected function getEnabledOffers($storeId): array
    {
        $productsCollection = $this->createOffersCollection($storeId);

        $productsCollection
            ->addAttributeToSelect(['daily_deal_from', 'daily_deal_to'])
            ->addFieldToFilter('daily_deal_enabled', ['eq' => 1]);

        return $productsCollection->getItems();
    }

    protected function getPendingOffers($currentDateFilter, $storeId): array
    {
        $productsCollection = $this->createOffersCollection($storeId);

        $productsCollection
            ->addFieldToFilter('daily_deal_from', ['lt' => $currentDateFilter])
            ->addFieldToFilter('daily_deal_to', ['gt' => $currentDateFilter])
            ->addFieldToFilter('daily_deal_enabled', ['eq' => 0]);

        return $productsCollection->getItems();
    }

    protected function createOffersCollection($storeId): \Magento\Catalog\Model\ResourceModel\Product\Collection
    {
        $productsCollection = $this->productCollectionFactory->create();

        $productsCollection
            ->setStoreId($storeId)
            ->addAttributeToSelect('daily_deal_limit');

        $linkField = $productsCollection->getEntity()->getLinkField();
        $productsCollection->getSelect()->order(sprintf('e.%s %s', $linkField, \Zend_Db_Select::SQL_ASC));

        return $this->addBackOrdersData($productsCollection);
    }

    protected function addBackOrdersData(\Magento\Catalog\Model\ResourceModel\Product\Collection $productsCollection): \Magento\Catalog\Model\ResourceModel\Product\Collection
    {
        if (!$this->configuration->isAllowBackOrdersEnabled()) {
            return $productsCollection;
        }

        $productsCollection->getSelect()->joinLeft('cataloginventory_stock_item',
            'cataloginventory_stock_item.product_id = e.entity_id',
            ['backorders' => 'backorders']
        );

        return $productsCollection;
    }

    public function getItemsByProductId($productId)
    {
        $select = $this->resource->getConnection()
            ->select()
            ->from(
                ['qo' => $this->resource->getTableName('quote_item_option')],
                ['q.entity_id', 'qo.item_id']
            )
            ->joinLeft(['qi' => $this->resource->getTableName('quote_item')], 'qi.item_id = qo.item_id', '')
            ->joinLeft(['q' => $this->resource->getTableName('quote')], 'q.entity_id = qi.quote_id', '')

            ->where('q.is_active = ?', 1)
            ->where('qo.product_id = ?', $productId)
            ->where('qo.code = ?', \MageSuite\DailyDeal\Service\OfferManager::ITEM_OPTION_DD_OFFER)
            ->where('qo.value = ?', 'true');

        return $this->resource->getConnection()->fetchAssoc($select);
    }

    public function getProductQtyInCart($productId, $quoteId): float
    {
        $connection = $this->resource->getConnection();

        $select = $connection
            ->select()
            ->from(
                ['qi' => $this->resource->getTableName('quote_item')],
                ['qty' => new \Zend_Db_Expr('SUM(qi.qty)')]
            )
            ->where('qi.product_id = ?', $productId)
            ->where('qi.quote_id = ?', $quoteId);

        return (float)$connection->fetchOne($select);
    }
}
