<?php

declare(strict_types=1);

namespace MageSuite\DailyDeal\Test\Integration\Block;

/**
 * @magentoDbIsolation enabled
 * @magentoAppIsolation enabled
 */
class ProductTest extends \PHPUnit\Framework\TestCase
{
    protected \Magento\Framework\App\ObjectManager $objectManager;
    protected \Magento\Framework\Registry $coreRegistry;
    protected \Magento\Catalog\Api\ProductRepositoryInterface $productRepository;
    protected \MageSuite\DailyDeal\Block\Product $productBlock;

    public function setUp(): void
    {
        $this->objectManager = \Magento\TestFramework\ObjectManager::getInstance();
        $this->coreRegistry = $this->objectManager->get(\Magento\Framework\Registry::class);
        $this->productRepository = $this->objectManager->get(\Magento\Catalog\Api\ProductRepositoryInterface::class);
        $this->productBlock = $this->objectManager->get(\MageSuite\DailyDeal\Block\Product::class);

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
     */
    public function testItReturnCorrectData(): void
    {
        $product = $this->productRepository->get('active_offer');
        $this->coreRegistry->register('product', $product);

        $offerData = $this->productBlock->getOfferData();

        $this->assertArrayHasKey('deal', $offerData);

        $this->assertTrue($offerData['deal']);

        $this->assertEquals(50, $offerData['items']);
        $this->assertEquals(1521417600, $offerData['from']);
        $this->assertEquals(1931932800, $offerData['to']);
        $this->assertEquals(5.00, $offerData['price'], '', 2);
        $this->assertEquals('none', $offerData['displayType']);

        $this->assertEquals(30, $offerData['oldDiscount']);

        $assertContains = method_exists($this, 'assertStringContainsString') ? 'assertStringContainsString' : 'assertContains';
        $assertNotContains = method_exists($this, 'assertStringNotContainsString') ? 'assertStringNotContainsString' : 'assertNotContains';

        $this->$assertContains('$10.00', $offerData['oldPriceHtmlOnTile']);
        $this->$assertNotContains('$5.00', $offerData['oldPriceHtmlOnTile']);
        $this->$assertContains('$10.00', $offerData['oldPriceHtmlOnPdp']);
        $this->$assertNotContains('$5.00', $offerData['oldPriceHtmlOnPdp']);
    }

    /**
     * @magentoAppArea frontend
     * @magentoAppIsolation enabled
     * @magentoDbIsolation enabled
     * @magentoConfigFixture current_store daily_deal/general/active 1
     */
    public function testItReturnsFalseWhenNoCurrentProductIsRegistered(): void
    {
        $this->coreRegistry->register('product', null);

        $offerData = $this->productBlock->getOfferData();

        $this->assertFalse($offerData);
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDataFixture MageSuite_DailyDeal::Test/Integration/_files/products.php
     * @magentoConfigFixture current_store daily_deal/general/active 0
     */
    public function testItReturnsFalseIfDailyDealIsNotActive(): void
    {
        $product = $this->productRepository->get('active_offer');

        $this->coreRegistry->register('product', $product);

        $offerData = $this->productBlock->getOfferData();

        $this->assertFalse($offerData);
    }
}
