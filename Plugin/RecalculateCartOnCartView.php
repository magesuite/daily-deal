<?php

namespace MageSuite\DailyDeal\Plugin;

class RecalculateCartOnCartView
{
    /**
     * @var \MageSuite\DailyDeal\Helper\Configuration
     */
    protected $configuration;
    /**
     * @var \Magento\Checkout\Model\Cart
     */
    protected $cart;

    public function __construct(
        \Magento\Checkout\Model\Cart $cart,
        \MageSuite\DailyDeal\Helper\Configuration $configuration
    ) {
        $this->cart = $cart;
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

        $this->cart->save();

        return null;
    }
}
