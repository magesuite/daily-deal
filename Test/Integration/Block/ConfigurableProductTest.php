<?php

namespace MageSuite\DailyDeal\Test\Integration\Block;

class ConfigurableProductTest extends \PHPUnit\Framework\TestCase
{
    protected ?\Magento\Framework\ObjectManagerInterface $objectManager = null;

    protected ?\Magento\Framework\Registry $coreRegistry = null;

    protected ?\Magento\Catalog\Api\ProductRepositoryInterface $productRepository = null;

    protected ?\MageSuite\DailyDeal\Block\Product $productBlock = null;

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
     * @magentoDbIsolation disabled
     * @magentoDataFixture MageSuite_DailyDeal::Test/Integration/_files/configurable_products.php
     * @magentoDataFixture Magento_InventoryIndexer::Test/_files/reindex_inventory.php
     * @magentoConfigFixture current_store daily_deal/general/active 1
     * @magentoConfigFixture current_store daily_deal/general/use_qty_limitation 1
     */
    public function testItReturnsCorrectDataForConfigurableProduct()
    {
        $product = $this->productRepository->get('configurable_12345');
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
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoDataFixture MageSuite_DailyDeal::Test/Integration/_files/configurable_products.php
     * @magentoDataFixture Magento_InventoryIndexer::Test/_files/reindex_inventory.php
     * @magentoConfigFixture current_store daily_deal/general/active 0
     */
    public function testItReturnsFalseIfDailyDealIsNotActive()
    {
        $product = $this->productRepository->get('configurable_12345');
        $this->coreRegistry->register('product', $product);
        $offerData = $this->productBlock->getOfferData();

        $this->assertFalse($offerData);
    }
}
