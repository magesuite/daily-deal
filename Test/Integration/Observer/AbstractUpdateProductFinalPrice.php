<?php

namespace MageSuite\DailyDeal\Test\Integration\Observer;

class AbstractUpdateProductFinalPrice extends \PHPUnit\Framework\TestCase
{
    protected ?\Magento\Framework\ObjectManagerInterface $objectManager;
    protected ?\MageSuite\DailyDeal\Helper\OfferData $offerData;
    protected ?\Magento\Catalog\Api\ProductRepositoryInterface $productRepository;
    protected ?\Magento\Catalog\Block\Product\View $productView;

    public function setUp(): void
    {
        $this->objectManager = \Magento\TestFramework\ObjectManager::getInstance();
        $this->offerData = $this->objectManager->get(\MageSuite\DailyDeal\Helper\OfferData::class);
        $this->productRepository =  $this->objectManager->get(\Magento\Catalog\Api\ProductRepositoryInterface::class);
        $this->productView = $this->objectManager->get(\Magento\Catalog\Block\Product\View::class);

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

    protected function runTileSimpleProductPriceTest()
    {
        $product = $this->productRepository->get('simple');
        $offerData = $this->offerData->prepareOfferData($product);
        $priceHtml = $this->productView->getProductPriceHtml(
            $product,
            \Magento\Catalog\Pricing\Price\FinalPrice::PRICE_CODE,
            \Magento\Framework\Pricing\Render::ZONE_ITEM_LIST,
            ['include_container' => true]
        );

        $this->assertEquals(2.30, (float) $offerData['price']);
        $this->assertEquals(
            2.30,
            (float) $product->getPriceInfo()
                ->getPrice(\Magento\Catalog\Pricing\Price\FinalPrice::PRICE_CODE)
                ->getAmount()
                ->getValue()
        );
        $this->assertEquals(
            10.00,
            (float) $product->getPriceInfo()
                ->getPrice(\Magento\Catalog\Pricing\Price\RegularPrice::PRICE_CODE)
                ->getAmount()
                ->getValue()
        );
        $this->assertStringContainsString('<span class="price">$2.30</span>', $priceHtml);
        $this->assertStringContainsString('<span class="price">$10.00</span>', $priceHtml);
    }
}
