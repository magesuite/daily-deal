<?php

namespace MageSuite\DailyDeal\Block;

class Product extends \Magento\Framework\View\Element\Template
{
    const CACHE_LIFETIME = 86400;
    const CACHE_TAG = 'daily_deal_product_%s_%s';

    protected $_template = 'MageSuite_DailyDeal::product.phtml'; // phpcs:ignore

    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * @var \MageSuite\DailyDeal\Helper\Configuration
     */
    protected $configuration;

    /**
     * @var \MageSuite\DailyDeal\Helper\OfferData
     */
    protected $offerData;

    /**
     * @var \Magento\Framework\App\CacheInterface
     */
    protected $cache;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Magento\Framework\Serialize\SerializerInterface
     */
    protected $serializer;

    public function __construct(
        \Magento\Catalog\Block\Product\Context $context,
        \Magento\Framework\Registry $registry,
        \MageSuite\DailyDeal\Helper\Configuration $configuration,
        \MageSuite\DailyDeal\Helper\OfferData $offerData,
        \Magento\Framework\App\CacheInterface $cache,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Serialize\SerializerInterface $serializer,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->registry = $registry;
        $this->configuration = $configuration;
        $this->offerData = $offerData;
        $this->cache = $cache;
        $this->storeManager = $storeManager;
        $this->serializer = $serializer;
    }

    public function getOfferData()
    {
        $isActive = $this->configuration->isActive();

        if (!$isActive) {
            return false;
        }

        $product = $this->getProduct();

        if (!$product) {
            return false;
        }

        $cacheTag = $this->getCacheTag($product->getId());

        $offerData = $this->cache->load($cacheTag);
        $offerData = $offerData ? $this->serializer->unserialize($offerData) : null;

        if (!$offerData) {
            $offerData = $this->offerData->prepareOfferData($product);

            $this->cache->save(
                $this->serializer->serialize($offerData),
                $cacheTag,
                array_merge([$cacheTag], $product->getIdentities()),
                self::CACHE_LIFETIME
            );
        }

        return $offerData;
    }

    public function getProduct()
    {
        $product = $this->registry->registry('product');

        return $product ? $product : false;
    }

    public function getCacheTag($productId)
    {
        return sprintf(
            self::CACHE_TAG,
            $productId,
            $this->storeManager->getStore()->getId()
        );
    }
}
