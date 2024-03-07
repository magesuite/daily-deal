<?php

namespace MageSuite\DailyDeal\Service;

class SalableStockResolver
{
    protected \Magento\Store\Model\StoreManagerInterface $storeManager;

    protected \Magento\InventorySalesApi\Api\GetProductSalableQtyInterface $getProductSalableQty;

    protected \Magento\InventorySalesApi\Api\StockResolverInterface $stockResolver;

    protected \Psr\Log\LoggerInterface $logger;

    protected array $productQuantityCache = [];

    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\InventorySalesApi\Api\GetProductSalableQtyInterface $getProductSalableQty,
        \Magento\InventorySalesApi\Api\StockResolverInterface $stockResolver,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->storeManager = $storeManager;
        $this->getProductSalableQty = $getProductSalableQty;
        $this->stockResolver = $stockResolver;
        $this->logger = $logger;
    }

    public function execute(\Magento\Catalog\Api\Data\ProductInterface $product, $storeId = null)
    {
        try {
            $store = $this->storeManager->getStore($storeId);
            $website = $store->getWebsite();
            $stockId = $this->stockResolver->execute(\Magento\InventorySalesApi\Api\Data\SalesChannelInterface::TYPE_WEBSITE, $website->getCode())->getStockId();

            $productSku = $product->getSku();
            if (!isset($this->productQuantityCache[$stockId][$productSku])) {
                $salableQty = $this->getSalableQuantity($product, $stockId);
                $this->productQuantityCache[$stockId][$productSku] = $salableQty;
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
            return $this->getProductSalableQty->execute($product->getSku(), $stockId);
        } elseif ($product->getTypeId() === \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE) {
            return $this->getQuantityForConfigurableProduct($product, $stockId);
        }

        throw new \Magento\Framework\Exception\InputException(__('Unsupported product type'));
    }

    protected function getQuantityForConfigurableProduct(\Magento\Catalog\Api\Data\ProductInterface $product, $stockId): float
    {
        $salableQty = 0;
        $typeInstance = $product->getTypeInstance();
        $usedProducts = $typeInstance->getUsedProducts($product);

        foreach ($usedProducts as $simpleProduct) {
            $salableQty += $this->getProductSalableQty->execute($simpleProduct->getSku(), $stockId);
        }

        return $salableQty;
    }
}
