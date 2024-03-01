<?php

namespace MageSuite\DailyDeal\Pricing\Price;

class FinalPriceWithoutDailyDeal extends \Magento\Framework\Pricing\Price\AbstractPrice implements \Magento\Framework\Pricing\Price\BasePriceProviderInterface
{
    /**
     * Price type
     */
    const PRICE_CODE = 'final_price_without_daily_deal';

    protected $value;

    /**
     * @var array
     */
    protected $excludedPriceClasses;

    public function __construct(
        \Magento\Framework\Pricing\SaleableInterface $saleableItem,
        $quantity,
        \Magento\Framework\Pricing\Adjustment\CalculatorInterface $calculator,
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency,
        $excludedPriceClasses = []
    ) {
        $this->excludedPriceClasses = $excludedPriceClasses;
        parent::__construct($saleableItem, $quantity, $calculator, $priceCurrency);
    }

    public function getValue()
    {
        $value = $this->getFinalPriceWithoutDailyDeal();

        if (!$value) {
            return false;
        }

        return $value;
    }

    public function getFinalPriceWithoutDailyDeal()
    {
        if ($this->value === null) {
            $this->value = false;
            foreach ($this->priceInfo->getPrices() as $price) {

                if ($this->isExcludedClass($price)) {
                    continue;
                }

                if ($price->getValue() !== false) {
                    $this->value = min($price->getValue(), $this->value ?: $price->getValue());
                }
            }
        }

        return $this->value;
    }

    private function isExcludedClass($class): bool
    {
        if ($this->getProduct()->getTypeId() == \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE) {
            $this->excludedPriceClasses[] = \Magento\Catalog\Pricing\Price\ConfiguredRegularPrice::class;
        }

        foreach ($this->excludedPriceClasses as $className) {
            if ($class instanceof $className) {
                return true;
            }
        }

        return false;
    }
}
