<?php

namespace MageSuite\DailyDeal\Plugin;

class DisableReorderingOffer
{
    /**
     * @var \MageSuite\DailyDeal\Helper\Configuration
     */
    protected $configuration;

    public function __construct(\MageSuite\DailyDeal\Helper\Configuration $configuration)
    {
        $this->configuration = $configuration;
    }

    public function aroundAddOrderItem(\Magento\Checkout\Model\Cart $subject, callable $proceed, $orderItem, $qtyFlag = null)
    {
        if (!$this->configuration->isActive()) {
            return $proceed($orderItem, $qtyFlag);
        }

        $buyRequest = $orderItem->getProductOptionByCode('info_buyRequest');

        $offerKey = \MageSuite\DailyDeal\Service\OfferManager::ITEM_OPTION_DD_OFFER;

        if (isset($buyRequest[$offerKey]) && $buyRequest[$offerKey]) {
            return $subject;
        }

        return $proceed($orderItem, $qtyFlag);
    }
}
