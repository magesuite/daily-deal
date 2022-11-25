<?php

namespace MageSuite\DailyDeal\Plugin\Quote\Model\Quote;

class PreventReorderingDailyDealOffer
{
    public const CUSTOMER_REORDER_PATH = 'sales_order_reorder';
    public const ADMIN_REORDER_PATH = 'sales_order_create_reorder';

    /**
     * @var array
     */
    protected $reorderActions = [
        self::ADMIN_REORDER_PATH,
        self::CUSTOMER_REORDER_PATH
    ];

    /**
     * @var \MageSuite\DailyDeal\Helper\Configuration
     */
    protected $configuration;

    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $request;

    /**
     * @param \MageSuite\DailyDeal\Helper\Configuration $configuration
     * @param \Magento\Framework\App\RequestInterface $request
     */
    public function __construct(
        \MageSuite\DailyDeal\Helper\Configuration $configuration,
        \Magento\Framework\App\RequestInterface $request
    ) {
        $this->configuration = $configuration;
        $this->request = $request;
    }

    /**
     * @param \Magento\Quote\Model\Quote $quote
     * @param callable $proceed
     * @param \Magento\Catalog\Model\Product $product
     * @param $request
     * @return \Magento\Quote\Model\Quote
     */
    public function aroundAddProduct(
        \Magento\Quote\Model\Quote $quote,
        callable $proceed,
        \Magento\Catalog\Model\Product $product,
        $request = null,
        $processMode = \Magento\Catalog\Model\Product\Type\AbstractType::PROCESS_MODE_FULL
    ) {
        if (!$this->configuration->isActive()) {
            return $proceed($product, $request, $processMode);
        }

        if (!$this->isReorderAction()) {
            return $proceed($product, $request, $processMode);
        }

        $offerKey = \MageSuite\DailyDeal\Service\OfferManager::ITEM_OPTION_DD_OFFER;
        $isDailyDeal = $request->getData($offerKey);

        if (!$isDailyDeal) {
            return $proceed($product, $request, $processMode);
        }

        return $quote;
    }

    /**
     * @return bool
     */
    public function isReorderAction(): bool
    {
        if (in_array($this->request->getFullActionname(), $this->reorderActions)) {
            return true;
        }

        return false;
    }
}
