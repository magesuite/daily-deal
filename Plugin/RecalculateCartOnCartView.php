<?php

namespace MageSuite\DailyDeal\Plugin;

class RecalculateCartOnCartView
{
    protected \MageSuite\DailyDeal\Helper\Configuration $configuration;
    protected \Magento\Checkout\Model\Session $session;
    protected \Magento\Checkout\Model\Cart $cart;

    public function __construct(
        \Magento\Checkout\Model\Cart $cart,
        \Magento\Checkout\Model\Session $session,
        \MageSuite\DailyDeal\Helper\Configuration $configuration
    ) {
        $this->cart = $cart;
        $this->session = $session;
        $this->configuration = $configuration;
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
}
