<?php

namespace MageSuite\DailyDeal\Plugin\ConfigurableProduct\Block\Product\View\Type\Configurable;

class AddDailyDealPricesToJsonConfig
{
    protected \MageSuite\DailyDeal\Helper\Configuration $configuration;

    protected \MageSuite\DailyDeal\Helper\OfferData $offerData;

    protected \Magento\Framework\Locale\Format $localeFormat;

    protected \Magento\Framework\Json\DecoderInterface $jsonDecoder;

    protected \Magento\Framework\Json\EncoderInterface $jsonEncoder;

    public function __construct(
        \MageSuite\DailyDeal\Helper\Configuration $configuration,
        \MageSuite\DailyDeal\Helper\OfferData $offerData,
        \Magento\Framework\Locale\Format $localeFormat,
        \Magento\Framework\Json\DecoderInterface $jsonDecoder,
        \Magento\Framework\Json\EncoderInterface $jsonEncoder
    ) {
        $this->localeFormat = $localeFormat;
        $this->offerData = $offerData;
        $this->configuration = $configuration;
        $this->jsonDecoder = $jsonDecoder;
        $this->jsonEncoder = $jsonEncoder;
    }

    public function afterGetJsonConfig(
        \Magento\ConfigurableProduct\Block\Product\View\Type\Configurable $subject,
        $result
    ) {
        if (!$this->configuration->isActive() && $this->offerData->isOfferEnabled($subject->getProduct()) === false) {
            return $result;
        }

        $result = $this->jsonDecoder->decode($result);
        foreach ($result['optionPrices'] as &$optionPrice) {
            $optionPrice['dailyDealOldPrice'] = [
                'amount' => $this->localeFormat->getNumber($subject->getProduct()->getPriceInfo()->getPrice('final_price_without_daily_deal')->getValue())
            ];
            $optionPrice['dailyDealPrice'] = [
                'amount' => $this->localeFormat->getNumber($subject->getProduct()->getPriceInfo()->getPrice('configurable_offer_price')->getValue())
            ];
        }

        return $this->jsonEncoder->encode($result);
    }
}
