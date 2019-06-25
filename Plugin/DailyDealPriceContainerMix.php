<?php

namespace MageSuite\DailyDeal\Plugin;

class DailyDealPriceContainerMix
{
    /**
     * @var \MageSuite\DailyDeal\Helper\OfferData
     */
    protected $offerDataHelper;

    public function __construct(\MageSuite\DailyDeal\Helper\OfferData $offerDataHelper)
    {
        $this->offerDataHelper = $offerDataHelper;
    }

    public function aroundGetData(\MageSuite\ProductTile\Block\Tile\Container $subject, callable $proceed, $key, $index = '') {

        $nameInLayout = $subject->getNameInLayout();

        $result = $proceed($key, $index);

        if(($nameInLayout == 'product.tile.price.wrapper.grid' || $nameInLayout == 'product.tile.price.wrapper.list') and $key == 'css_class') {
            $product = $subject->getProduct();

            if(!$product) {
                return $result;
            }

            $dailyDealData = $this->offerDataHelper->prepareOfferData($product);

            if($dailyDealData && $dailyDealData['deal'] && $dailyDealData['displayType'] === 'badge_counter') {
                $result .= ' cs-product-tile__price--dailydeal-countdown';
            }
        }

        return $result;
    }
}
