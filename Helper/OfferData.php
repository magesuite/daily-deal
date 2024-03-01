<?php

namespace MageSuite\DailyDeal\Helper;

class OfferData extends \Magento\Framework\App\Helper\AbstractHelper
{
    const AVAILABLE_PRODUCT_TYPES = [
        \Magento\Catalog\Model\Product\Type::TYPE_SIMPLE,
        \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE
    ];

    protected \MageSuite\DailyDeal\Helper\Configuration $configuration;

    protected \Magento\Catalog\Api\ProductRepositoryInterface $productRepository;

    protected \Magento\Framework\Stdlib\DateTime\DateTime $dateTime;

    protected \Magento\Catalog\Block\Product\View $productView;

    protected \MageSuite\Discount\Helper\Discount $discountHelper;

    protected \MageSuite\DailyDeal\Service\SalableStockResolver $salableStockResolver;

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \MageSuite\DailyDeal\Helper\Configuration $configuration,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
        \Magento\Catalog\Block\Product\View $productView,
        \MageSuite\Discount\Helper\Discount $discountHelper,
        \MageSuite\DailyDeal\Service\SalableStockResolver $salableStockResolver
    ) {
        parent::__construct($context);

        $this->configuration = $configuration;
        $this->productRepository = $productRepository;
        $this->dateTime = $dateTime;
        $this->productView = $productView;
        $this->discountHelper = $discountHelper;
        $this->salableStockResolver = $salableStockResolver;
    }

    public function prepareOfferData(\Magento\Catalog\Api\Data\ProductInterface $product): ?array
    {
        $isActive = $this->configuration->isActive();
        if (!$isActive) {
            return null;
        }

        $product = $this->getProduct($product);
        if (!$product) {
            return null;
        }

        if ($product->getData('daily_deal_offer_data')) {
            return $product->getData('daily_deal_offer_data');
        }

        $isQtyLimitationEnabled = $this->configuration->isQtyLimitationEnabled();
        $salableQty = $this->salableStockResolver->execute($product);
        $result = [
            'deal' => $this->isOfferEnabled($product),
            'items' => $isQtyLimitationEnabled ? ($this->getOfferLimit($product) > $salableQty ? $salableQty : $this->getOfferLimit($product)) : 0,
            'from' => $product->getDailyDealFrom() === null ? null : strtotime($product->getDailyDealFrom()),
            'initialAmount' => $product->getDailyDealInitialAmount(),
            'to' => $product->getDailyDealTo() === null ? null : strtotime($product->getDailyDealTo()),
            'price' => $product->getDailyDealPrice(),
            'displayType' => $this->displayOnTile()
        ];

        if ($result['deal']) {
            $result['dailyDiscount'] = $this->discountHelper->getSalePercentage($product, $result['price']);
            $priceAndDiscountWithoutDD = $this->getPriceAndDiscountWithoutDD($product);
            $result = array_merge($result, $priceAndDiscountWithoutDD);
        }

        $product->setData('daily_deal_offer_data', $result);

        return $result;
    }

    public function isOfferEnabled(\Magento\Catalog\Api\Data\ProductInterface $product): bool
    {
        $product = $this->getProduct($product);
        if (!$product || !$product->getId()) {
            return false;
        }

        $offerEnabled = (boolean)$product->getDailyDealEnabled();
        if (!$offerEnabled) {
            return false;
        }

        if (!in_array($product->getTypeId(), self::AVAILABLE_PRODUCT_TYPES)) {
            return false;
        }

        if (!$product->getIsSalable()) {
            return false;
        }

        if ($this->salableStockResolver->execute($product) < 0) {
            return false;
        }

        $offerTo = $product->getDailyDealTo();

        if($offerTo === null) {
            return true;
        }

        return $this->dateTime->gmtTimestamp() < strtotime($offerTo);
    }

    public function getPriceAndDiscountWithoutDD($product)
    {
        $result = [
            'oldDiscount' => $this->getDiscountWithoutDD($product)
        ];

        $result['oldPriceHtmlOnTile'] = $this->productView->getProductPriceHtml(
            $product,
            \MageSuite\DailyDeal\Pricing\Price\FinalPriceWithoutDailyDeal::PRICE_CODE,
            \Magento\Framework\Pricing\Render::ZONE_ITEM_LIST,
            ['include_container' => true]
        );

        $result['oldPriceHtmlOnPdp'] = $this->productView->getProductPriceHtml(
            $product,
            \MageSuite\DailyDeal\Pricing\Price\FinalPriceWithoutDailyDeal::PRICE_CODE,
            \Magento\Framework\Pricing\Render::ZONE_ITEM_VIEW,
            ['include_container' => true]
        );

        return $result;
    }

    public function getDiscountWithoutDD(\Magento\Catalog\Api\Data\ProductInterface $product)
    {
        $finalPriceWithoutDailyDeal = $product->getPriceInfo()->getPrice(\MageSuite\DailyDeal\Pricing\Price\FinalPriceWithoutDailyDeal::PRICE_CODE)->getAmount()->getValue();
        return $this->discountHelper->getSalePercentage($product, $finalPriceWithoutDailyDeal);
    }

    public function displayOnTile()
    {
        return $this->configuration->displayOnTile();
    }

    private function getOfferLimit($product)
    {
        $offerLimit = $product->getDailyDealLimit();
        $quantityAndStockStatus = $product->getQuantityAndStockStatus();

        if (!$quantityAndStockStatus || $product->getTypeId() === \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE) {
            return $offerLimit;
        }

        $qty = isset($quantityAndStockStatus['qty']) ? $quantityAndStockStatus['qty'] : null;

        if ($qty === null || $qty < 0) {
            return $offerLimit;
        }

        return min($qty, $offerLimit);
    }

    private function getProduct($product)
    {
        if ($product instanceof \Magento\Catalog\Api\Data\ProductInterface) {
            return $product;
        }

        if (!is_int($product) && !is_string($product)) {
            return null;
        }

        try {
            $product = $this->productRepository->getById($product);
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            return null;
        }

        return $product;
    }

    public function isDailyDealCounterApplicable($dailyDealData, $dailyDealCounterPlace)
    {
        return $dailyDealData && $dailyDealData['deal'] && ($dailyDealCounterPlace === 'pdp' || ($dailyDealCounterPlace === 'tile' && $dailyDealData['displayType'] === 'badge_counter'));
    }

    public function isDailyDealPriceApplicable($dailyDealData)
    {
        return $dailyDealData && $dailyDealData['deal'] && $dailyDealData['displayType'] !== 'none';
    }
}
