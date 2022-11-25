<?php

namespace MageSuite\DailyDeal\Service;

class SalableStockResolver
{
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Magento\InventorySalesApi\Api\GetProductSalableQtyInterface
     */
    protected $getProductSalableQty;

    /**
     * @var \Magento\InventorySalesApi\Api\StockResolverInterface
     */
    protected $stockResolver;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @var array
     */
    protected $productQuantityCache = [];

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

    public function execute(string $productSku, $storeId = null)
    {
        try {
            $store = $this->storeManager->getStore($storeId);
            $website = $store->getWebsite();
            $stockId = $this->stockResolver->execute(
                \Magento\InventorySalesApi\Api\Data\SalesChannelInterface::TYPE_WEBSITE,
                $website->getCode()
            )->getStockId();

            if (!isset($this->productQuantityCache[$stockId][$productSku])) {
                $salableQty = $this->getProductSalableQty->execute(
                    $productSku,
                    $stockId
                );
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
}
