<?php

namespace MageSuite\DailyDeal\Block;

class Product extends \Magento\Framework\View\Element\Template
{
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

    public function __construct(
        \Magento\Catalog\Block\Product\Context $context,
        \Magento\Framework\Registry $registry,
        \MageSuite\DailyDeal\Helper\Configuration $configuration,
        \MageSuite\DailyDeal\Helper\OfferData $offerData,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->registry = $registry;
        $this->configuration = $configuration;
        $this->offerData = $offerData;
    }

    public function getOfferData(): array|false|null
    {
        if (!$this->configuration->isActive()) {
            return false;
        }

        $product = $this->getProduct();

        if (!$product) {
            return false;
        }

        return $this->offerData->prepareOfferData($product);
    }

    public function getProduct(): \Magento\Catalog\Model\Product|false
    {
        $product = $this->registry->registry('product');

        return $product ? $product : false;
    }
}
