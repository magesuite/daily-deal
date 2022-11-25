<?php

namespace MageSuite\DailyDeal\Plugin\ProductTile\Block\Tile\Container;

class DailyDealPriceContainerMix
{
    /**
     * @var \MageSuite\DailyDeal\Helper\OfferData
     */
    protected $offerDataHelper;
    /**
     * @var \MageSuite\DailyDeal\Helper\Configuration
     */
    protected $configuration;

    public function __construct(
        \MageSuite\DailyDeal\Helper\OfferData $offerDataHelper,
        \MageSuite\DailyDeal\Helper\Configuration $configuration
    ) {
        $this->offerDataHelper = $offerDataHelper;
        $this->configuration = $configuration;
    }

    public function aroundGetData(\MageSuite\ProductTile\Block\Tile\Container $subject, callable $proceed, $key = '', $index = null)
    {
        $result = $proceed($key, $index);

        if (!$this->configuration->isActive()) {
            return $result;
        }

        $nameInLayout = $subject->getNameInLayout();

        if (($nameInLayout == 'product.tile.price.wrapper.grid' || $nameInLayout == 'product.tile.price.wrapper.list') && $key == 'css_class') {
            $product = $subject->getProduct();

            if (!$product) {
                return $result;
            }

            if (!$this->offerDataHelper->isOfferEnabled($product)) {
                return $result;
            }

            $dailyDealData = $this->offerDataHelper->prepareOfferData($product);

            if ($dailyDealData && $dailyDealData['deal'] && $dailyDealData['displayType'] === 'badge_counter') {
                $result .= ' cs-product-tile__price--dailydeal-countdown';
            }
        }

        return $result;
    }
}
