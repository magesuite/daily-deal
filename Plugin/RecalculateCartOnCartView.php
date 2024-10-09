<?php

namespace MageSuite\DailyDeal\Plugin;

class RecalculateCartOnCartView
{
    protected \MageSuite\DailyDeal\Helper\Configuration $configuration;
    protected \Magento\Checkout\Model\Session $session;
    protected \Magento\Checkout\Model\Cart $cart;
    protected \MageSuite\DailyDeal\Service\DailyDealApplier $dailyDealApplier;

    public function __construct(
        \Magento\Checkout\Model\Cart $cart,
        \Magento\Checkout\Model\Session $session,
        \MageSuite\DailyDeal\Helper\Configuration $configuration,
        \MageSuite\DailyDeal\Service\DailyDealApplier $dailyDealApplier
    ) {
        $this->cart = $cart;
        $this->session = $session;
        $this->configuration = $configuration;
        $this->dailyDealApplier = $dailyDealApplier;
    }

    /**
     * We need to always have recalculated cart items data before viewing cart
     * @param \Magento\Checkout\Controller\Cart\Index $subject
     * @return null
     */
    public function beforeExecute(\Magento\Checkout\Controller\Cart\Index $subject)
    {
        if (!$this->configuration->isActive()) {
            return null;
        }

        if ($this->isNeedToRecalculateCart()) {
            $this->updateCustomPrices();
            $this->cart->save();
        }

        return null;
    }

    protected function isNeedToRecalculateCart(): bool
    {
        if (!$this->session->hasQuote()) {
            return false;
        }

        $quote = $this->session->getQuote();

        /** @var \Magento\Quote\Model\Quote\Item $item */
        foreach ($quote->getAllItems() as $item) {
            if (!$item->getOptionByCode('is_daily_deal')) {
                continue;
            }

            return true;
        }

        return false;
    }

    protected function updateCustomPrices()
    {
        foreach ($this->cart->getItems() as $cartItem) {
            if ($cartItem->getParentItemId() === null) {
                $this->dailyDealApplier->apply($cartItem);
            }
        }
    }
}
