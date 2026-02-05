<?php

declare(strict_types=1);

namespace MageSuite\DailyDeal\Test\Integration\Pricing\Price;

/**
 * @magentoDbIsolation enabled
 * @magentoAppIsolation enabled
 */
class FinalPriceWithoutDailyDealTest extends \PHPUnit\Framework\TestCase
{
    protected \Magento\Framework\App\ObjectManager $objectManager;
    protected \Magento\Catalog\Api\ProductRepositoryInterface $productRepository;

    public function setUp(): void
    {
        $this->objectManager = \Magento\TestFramework\ObjectManager::getInstance();
        $this->productRepository = $this->objectManager->get(\Magento\Catalog\Api\ProductRepositoryInterface::class);

        $priceRender = $this->objectManager->get(\Magento\Framework\View\LayoutInterface::class)->getBlock('product.price.render.default');

        if (!$priceRender) {
            $this->objectManager->get(\Magento\Framework\View\LayoutInterface::class)->createBlock(
                \Magento\Framework\Pricing\Render::class,
                'product.price.render.default',
                [
                    'data' => [
                        'price_render_handle' => 'catalog_product_prices',
                    ],
                ]
            );
        }
    }

    /**
     * @magentoAppArea frontend
     * @magentoAppIsolation enabled
     * @magentoDbIsolation enabled
     * @magentoDataFixture MageSuite_DailyDeal::Test/Integration/_files/products.php
     * @magentoConfigFixture current_store daily_deal/general/active 1
     * @magentoConfigFixture current_store daily_deal/general/use_qty_limitation 1
     * @dataProvider dataProvider
     */
    public function testItReturnCorrectPrice(string $priceCode, int $expectedValue): void
    {
        $product = $this->productRepository->get('active_offer');

        $this->assertEquals($expectedValue, $product->getPriceInfo()->getPrice($priceCode)->getAmount()->getValue());
    }

    /**
     * @return array
     */
    public static function dataProvider(): array
    {
        return [
            ['regular_price', 10],
            ['final_price', 5],
            ['special_price', 7],
            ['final_price_without_daily_deal', 7]
        ];
    }
}
