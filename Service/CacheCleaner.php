<?php

namespace MageSuite\DailyDeal\Service;

class CacheCleaner
{
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
        \Magento\Framework\App\CacheInterface $cache,
        \Magento\Framework\Indexer\CacheContext $cacheContext,
        \Magento\Framework\Event\Manager $eventManager
    ) {
        $this->cache = $cache;
        $this->cacheContext = $cacheContext;
        $this->eventManager = $eventManager;
    }

    public function refreshProductCache(?\Magento\Catalog\Api\Data\ProductInterface $product): void
    {
        if (!$product) {
            return;
        }

        $tags = array_merge($product->getIdentities(), ['virtual_category']);

        if (empty($tags)) {
            return;
        }

        $this->cache->clean($tags);
        $this->cacheContext->registerTags($tags);
        $this->eventManager->dispatch('clean_cache_by_tags', ['object' => $this->cacheContext]);
    }
}
