<?php

namespace MageSuite\DailyDeal\Service;

interface OfferManagerInterface
{
    public function getOffers();

    public function applyAction($product, $action);

    public function refreshProductCache($product);

    public function getOfferPrice($product);

    public function getOfferLimit($product);

    public function validateOfferInQuote($product, $qty);

    public function decreaseOfferLimit($product, $qty);

    public function getParentProduct($product);
}
