<?php

namespace MageSuite\DailyDeal\Service;

class OfferManager implements \MageSuite\DailyDeal\Service\OfferManagerInterface
{
    const ITEM_OPTION_DD_OFFER = 'is_daily_deal';

    const TYPE_ADD = 1;
    const TYPE_REMOVE = 0;

    protected $timestamp;
    protected $storeId;
    protected $productsQuantities = [];

    protected \Magento\Quote\Api\CartRepositoryInterface $quoteRepository;

    protected \Magento\Quote\Model\Quote\TotalsCollector $totalsCollector;

    protected \Magento\Framework\Stdlib\DateTime\DateTime $dateTime;

    protected \Magento\Store\Model\StoreManagerInterface $storeManager;

    protected \MageSuite\DailyDeal\Helper\Configuration $configuration;

    protected \MageSuite\DailyDeal\Helper\OfferData $offerData;

    protected \MageSuite\DailyDeal\Model\ResourceModel\Offer $offerResource;

    protected \MageSuite\DailyDeal\Service\CacheCleaner $cacheCleaner;

    protected \Magento\Indexer\Model\IndexerFactory $indexerFactory;

    protected \MageSuite\DailyDeal\Service\SalableStockResolver $salableStockResolver;

    protected \Magento\Catalog\Model\ResourceModel\Product\Action $productResourceAction;

    public function __construct(
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Magento\Quote\Model\Quote\TotalsCollector $totalsCollector,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \MageSuite\DailyDeal\Helper\Configuration $configuration,
        \MageSuite\DailyDeal\Helper\OfferData $offerData,
        \MageSuite\DailyDeal\Model\ResourceModel\Offer $offerResource,
        \MageSuite\DailyDeal\Service\CacheCleaner $cacheCleaner,
        \Magento\Indexer\Model\IndexerFactory $indexerFactory,
        \MageSuite\DailyDeal\Service\SalableStockResolver $salableStockResolver,
        \Magento\Catalog\Model\ResourceModel\Product\Action $productResourceAction
    ) {
        $this->quoteRepository = $quoteRepository;
        $this->totalsCollector = $totalsCollector;
        $this->dateTime = $dateTime;
        $this->storeManager = $storeManager;
        $this->configuration = $configuration;
        $this->offerData = $offerData;
        $this->offerResource = $offerResource;
        $this->cacheCleaner = $cacheCleaner;
        $this->indexerFactory = $indexerFactory;
        $this->salableStockResolver = $salableStockResolver;
        $this->productResourceAction = $productResourceAction;
    }

    public function refreshOffers(?int $storeId = null): int
    {
        $this->setStoreId($storeId);
        $offers = $this->getOffers();
        $amountOfChangedOffers = 0;

        if (empty($offers)) {
            return $amountOfChangedOffers;
        }

        $this->getProductsQuantities($offers);
        $isQtyLimitationEnabled = $this->configuration->isQtyLimitationEnabled();

        foreach ($offers as $offer) {
            $action = $this->getOfferAction($offer, $isQtyLimitationEnabled, $storeId);

            if ($action === null) {
                continue;
            }

            $this->applyAction($offer, $action);
            $amountOfChangedOffers++;
        }

        return $amountOfChangedOffers;
    }

    public function getOffers()
    {
        $this->timestamp = $this->dateTime->gmtTimestamp();

        $items = $this->offerResource->getOffersByParameters($this->timestamp, $this->storeId);

        return $items;
    }

    public function getOfferAction($offer, $isQtyLimitationEnabled, $storeId)
    {
        $offerData = $offer->getData();

        $from = $offerData['daily_deal_from'] ? strtotime($offerData['daily_deal_from']) : null;
        $to = $offerData['daily_deal_to'] ? strtotime($offerData['daily_deal_to']) : null;

        $productQty = null;
        if ($offer->getTypeId() == \Magento\Catalog\Model\Product\Type::TYPE_SIMPLE && isset($this->productsQuantities[$offerData['entity_id']])) {
            $productQty = $this->productsQuantities[$offerData['entity_id']];
        }

        if ($offerData['daily_deal_enabled']) {

            // $productQty should be checked only for frontend stores, it doesn't make sense to check it for admin store
            if ($storeId != \Magento\Store\Model\Store::DEFAULT_STORE_ID && $productQty !== null && $productQty < 1) {
                return self::TYPE_REMOVE;
            }

            if (!$from || !$to) {
                return self::TYPE_REMOVE;
            }

            if ($from > $this->timestamp || $to < $this->timestamp) {
                return self::TYPE_REMOVE;
            }

            if ($isQtyLimitationEnabled && isset($offerData['daily_deal_limit']) // phpcs:ignore
                && $offerData['daily_deal_limit'] !== null
                && (float)$offerData['daily_deal_limit'] == 0) {
                return self::TYPE_REMOVE;
            }

        } else {

            if ($productQty !== null && $productQty < 1) {
                return null;
            }

            if (!$from || !$to) {
                return null;
            }

            if ($isQtyLimitationEnabled // phpcs:ignore
                && isset($offerData['daily_deal_limit'])
                && $offerData['daily_deal_limit'] !== null
                && (float)$offerData['daily_deal_limit'] == 0) {
                return null;
            }

            if ($from < $this->timestamp && $to > $this->timestamp) {
                return self::TYPE_ADD;
            }
        }

        return null;
    }

    public function applyAction($product, $action)
    {
        $product->setDailyDealEnabled($action);

        if ($product->getExistsStoreValueFlag('daily_deal_enabled')) {
            $product->getResource()->saveAttribute($product, 'daily_deal_enabled');
        }

        $this->productResourceAction->updateAttributes(
            [$product->getId()],
            [
                'daily_deal_enabled' => $action
            ],
            \Magento\Store\Model\Store::DEFAULT_STORE_ID
        );

        if ($action == self::TYPE_REMOVE) {
            $this->removeProductFromQuotes($product);
        }

        $this->refreshProductIndex($product);
        $this->refreshProductCache($product);
    }

    private function removeProductFromQuotes($product)
    {
        $items = $this->offerResource->getItemsByProductId($product->getId());

        if (empty($items)) {
            return true;
        }

        foreach ($items as $quoteId => $item) {
            $quote = $this->quoteRepository->get($quoteId);

            foreach ($quote->getItems() as $quoteItem) {
                if ($quoteItem->getId() != $item['item_id']) {
                    continue;
                }

                $quote->deleteItem($quoteItem);
            }

            $this->totalsCollector->collect($quote);
            $this->quoteRepository->save($quote->collectTotals());
        }

        return true;
    }

    public function refreshProductIndex($product)
    {
        $indexes = ['catalogsearch_fulltext', 'catalog_product_price'];

        foreach ($indexes as $indexId) {
            /** @var \Magento\Indexer\Model\Indexer $indexer */
            $indexer = $this->indexerFactory->create();
            $indexer->load($indexId);
            $indexer->reindexRow($product->getId());
        }
    }

    public function refreshProductCache($product)
    {
        $this->cacheCleaner->refreshProductCache($product);
    }

    public function getOfferPrice($product)
    {
        $this->setStoreId(null);

        if (!$this->offerData->isOfferEnabled($product)) {
            return null;
        }

        return $product->getDailyDealPrice();
    }

    public function getOfferLimit($product)
    {
        if (!$this->offerData->isOfferEnabled($product)) {
            return null;
        }

        return $product->getDailyDealLimit();
    }

    public function getProductQtyInCart($product, $quoteId)
    {
        return $this->offerResource->getProductQtyInCart($product->getId(), $quoteId);
    }

    public function validateOfferInQuote($product, $qty)
    {
        if (!$qty) {
            return false;
        }

        if (!$this->offerData->isOfferEnabled($product)) {
            return false;
        }

        $isQtyLimitationEnabled = $this->configuration->isQtyLimitationEnabled();

        if (!$isQtyLimitationEnabled) {
            return true;
        }

        $limit = $product->getDailyDealLimit();

        return $limit === null || (float)$qty <= (float)$limit;
    }

    /**
     * @param $product
     * @param null $qty
     * @return bool
     */
    public function decreaseOfferLimit($product, $qty = null)
    {
        if (!$this->offerData->isOfferEnabled($product)) {
            return true;
        }

        if (!$this->configuration->isQtyLimitationEnabled()) {
            return true;
        }

        $currentValue = $product->getDailyDealLimit();

        if ($currentValue === null) {
            return true;
        }

        $qty = $qty ? $qty : 1;

        $newValue = max(0, $currentValue - $qty);

        $product->setDailyDealLimit($newValue);

        if ($product->getExistsStoreValueFlag('daily_deal_limit')) {
            $product->getResource()->saveAttribute($product, 'daily_deal_limit');
        }

        $this->productResourceAction->updateAttributes(
            [$product->getId()],
            [
                'daily_deal_limit' => $newValue
            ],
            \Magento\Store\Model\Store::DEFAULT_STORE_ID
        );

        if ($newValue == 0) {
            $this->applyAction($product, self::TYPE_REMOVE);
        }

        $this->refreshProductIndex($product);
        $this->refreshProductCache($product);

        return true;
    }

    public function setTimestamp($timestamp)
    {
        $this->timestamp = $timestamp;
    }

    public function setStoreId($storeId)
    {
        $this->storeId = $storeId !== null
            ? $storeId
            : $this->storeManager->getStore()->getId();
    }

    protected function getProductsQuantities($items)
    {
        $qtys = [];
        foreach ($items as $product) {
            $qtys[$product->getId()] = $this->salableStockResolver->execute($product, $this->storeId);
        }

        $this->productsQuantities = $qtys;
    }
}
