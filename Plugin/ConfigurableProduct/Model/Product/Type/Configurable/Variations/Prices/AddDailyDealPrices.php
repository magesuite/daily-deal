<?php

namespace MageSuite\DailyDeal\Plugin\ConfigurableProduct\Model\Product\Type\Configurable\Variations\Prices;

class AddDailyDealPrices
{
    protected \MageSuite\DailyDeal\Helper\Configuration $configuration;

    protected \Magento\Framework\Locale\Format $localeFormat;

    public function __construct(
        \MageSuite\DailyDeal\Helper\Configuration $configuration,
        \Magento\Framework\Locale\Format $localeFormat
    ) {
        $this->configuration = $configuration;
        $this->localeFormat = $localeFormat;
    }

    public function afterGetFormattedPrices(
        \Magento\ConfigurableProduct\Model\Product\Type\Configurable\Variations\Prices $subject,
        $result,
        \Magento\Framework\Pricing\PriceInfo\Base $priceInfo
    ) {
        if (!$this->configuration->isActive()) {
            return $result;
        }

        $result['dailyDealOldPrice'] = [
            'amount' => $this->localeFormat->getNumber($priceInfo->getPrice('final_price_without_daily_deal')->getValue())
        ];
        $result['dailyDealPrice'] = [
            'amount' => $this->localeFormat->getNumber($priceInfo->getPrice('configurable_offer_price')->getValue())
        ];

        return $result;
    }
}
