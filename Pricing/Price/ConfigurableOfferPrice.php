<?php

namespace MageSuite\DailyDeal\Pricing\Price;

class ConfigurableOfferPrice extends \Magento\Framework\Pricing\Price\AbstractPrice implements \Magento\Framework\Pricing\Price\BasePriceProviderInterface
{
    const PRICE_CODE = 'configurable_offer_price';

    protected $value;

    protected \Magento\Framework\Pricing\SaleableInterface $saleableItem;

    protected \MageSuite\DailyDeal\Helper\Configuration $configuration;

    protected \MageSuite\DailyDeal\Service\OfferManagerInterface $offerManager;

    public function __construct(
        \Magento\Framework\Pricing\SaleableInterface $saleableItem,
        $quantity,
        \Magento\Framework\Pricing\Adjustment\CalculatorInterface $calculator,
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency,
        \MageSuite\DailyDeal\Helper\Configuration $configuration,
        \MageSuite\DailyDeal\Service\OfferManagerInterface $offerManager
    ) {
        parent::__construct($saleableItem, $quantity, $calculator, $priceCurrency);

        $this->saleableItem = $saleableItem;
        $this->configuration = $configuration;
        $this->offerManager = $offerManager;
    }

    public function getValue($exclude = null)
    {
        if (!$this->configuration->isActive()) {
            return false;
        }

        if ($this->value === null) {
            $price = $this->offerManager->getOfferPrice($this->saleableItem);
            $priceInCurrentCurrency = $this->priceCurrency->convertAndRound($price);
            $this->value = $priceInCurrentCurrency ? floatval($priceInCurrentCurrency) : false;
        }

        return $this->value;
    }
}
