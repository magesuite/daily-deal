<?php

namespace MageSuite\DailyDeal\Plugin\Framework\Pricing\Render;

class UseConfigurableOfferPriceForProductsWithDailyDeal
{
    public function beforeRender(
        \Magento\Framework\Pricing\Render $subject,
        $priceCode,
        \Magento\Framework\Pricing\SaleableInterface $saleableItem,
        array $arguments = []
    ) {
        if ($priceCode === \Magento\Catalog\Pricing\Price\FinalPrice::PRICE_CODE && $saleableItem->getTypeId() === 'configurable' && $saleableItem->getDailyDealEnabled()) {
            $priceCode = \MageSuite\DailyDeal\Pricing\Price\ConfigurableOfferPrice::PRICE_CODE;
        }

        return [$priceCode, $saleableItem, $arguments];
    }
}
