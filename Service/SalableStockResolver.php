<?php

declare(strict_types=1);

namespace MageSuite\DailyDeal\Service;

class SalableStockResolver
{
    protected array $productQuantityCache = [];

    public function __construct(
        protected \Magento\Store\Model\StoreManagerInterface $storeManager,
        protected \Magento\InventorySalesApi\Api\StockResolverInterface $stockResolver,
        protected \Magento\InventoryIndexer\Model\StockIndexTableNameResolverInterface $indexTableNameResolver,
        protected \Magento\Framework\App\ResourceConnection $resourceConnection,
        protected \Psr\Log\LoggerInterface $logger
    ) {}

    public function execute(\Magento\Catalog\Api\Data\ProductInterface $product, ?int $storeId = null): ?float
    {
        try {
            if ($storeId == \Magento\Store\Model\Store::DEFAULT_STORE_ID) {
                $store = $this->storeManager->getDefaultStoreView();
            } else {
                $store = $this->storeManager->getStore($storeId);
            }

            $website = $store->getWebsite();
            $stockId = $this->stockResolver->execute(\Magento\InventorySalesApi\Api\Data\SalesChannelInterface::TYPE_WEBSITE, $website->getCode())->getStockId();
            $productSku = $product->getSku();

            if (!isset($this->productQuantityCache[$stockId][$productSku])) {
                $this->productQuantityCache[$stockId][$productSku] = $this->getSalableQuantity($product, $stockId);
            }

            return $this->productQuantityCache[$stockId][$productSku];
        } catch (\Magento\Framework\Exception\NoSuchEntityException // phpcs:ignore
        | \Magento\Framework\Exception\InputException
        | \Magento\Framework\Exception\LocalizedException $e) {
            // do nothing
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }

        return null;
    }

    protected function getSalableQuantity(\Magento\Catalog\Api\Data\ProductInterface $product, int $stockId): float
    {
        if ($product->getTypeId() === \Magento\Catalog\Model\Product\Type::TYPE_SIMPLE) {
            return $this->getSalableQuantityByStockId([$product->getSku()], $stockId);
        } elseif ($product->getTypeId() === \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE) {
            return $this->getQuantityForConfigurableProduct($product, $stockId);
        }

        throw new \Magento\Framework\Exception\InputException(__('Unsupported product type'));
    }

    protected function getQuantityForConfigurableProduct(\Magento\Catalog\Api\Data\ProductInterface $product, int $stockId): float
    {
        $usedProducts = $product->getTypeInstance()->getUsedProducts($product);
        $skuList = [];

        foreach ($usedProducts as $simpleProduct) {
            $skuList[] = $simpleProduct->getSku();
        }

        if (empty($skuList)) {
            return 0.00;
        }

        return $this->getSalableQuantityByStockId($skuList, $stockId);
    }

    protected function getSalableQuantityByStockId(array $skuList, int $stockId): float
    {
        $tableName = $this->indexTableNameResolver->execute($stockId);
        $connection = $this->resourceConnection->getConnection();
        $select = $connection->select()
            ->from($tableName, ['SUM(quantity)'])
            ->where('sku IN (?)', $skuList)
            ->where('is_salable = ?', 1);

        return (float)$connection->fetchOne($select);
    }
}
