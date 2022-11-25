<?php

namespace MageSuite\DailyDeal\Service;

class CacheCleaner
{
    /**
     * @var \MageSuite\DailyDeal\Block\Product
     */
    protected $productBlock;

    /**
     * @var \Magento\Framework\App\CacheInterface
     */
    protected $cache;

    /**
     * @var \Magento\Framework\Indexer\CacheContext
     */
    protected $cacheContext;

    /**
     * @var \Magento\Framework\Event\Manager
     */
    protected $eventManager;

    public function __construct(
        \MageSuite\DailyDeal\Block\Product $productBlock,
        \Magento\Framework\App\CacheInterface $cache,
        \Magento\Framework\Indexer\CacheContext $cacheContext,
        \Magento\Framework\Event\Manager $eventManager
    ) {
        $this->productBlock = $productBlock;
        $this->cache = $cache;
        $this->cacheContext = $cacheContext;
        $this->eventManager = $eventManager;
    }

    public function refreshProductCache($product)
    {
        if (!$product) {
            return false;
        }

        $blockCacheTag = $this->productBlock->getCacheTag($product->getId());
        $tags = array_merge($product->getIdentities(), [$blockCacheTag, 'virtual_category']);

        if (empty($tags)) {
            return false;
        }

        $this->cache->clean($tags);
        $this->cacheContext->registerTags($tags);
        $this->eventManager->dispatch('clean_cache_by_tags', ['object' => $this->cacheContext]);
    }
}
