<?php

namespace Integration\Service;

class DailyDealApplierTest extends \Magento\TestFramework\TestCase\AbstractController
{
    protected ?\Magento\Checkout\Model\Cart $cart = null;
    protected ?\Magento\Framework\ObjectManagerInterface $objectManager = null;
    protected ?\MageSuite\DailyDeal\Service\OfferManager $offerManager = null;
    protected ?\MageSuite\DailyDeal\Model\ResourceModel\Offer $offerResource = null;
    protected ?\Magento\Catalog\Api\ProductRepositoryInterface $productRepository = null;
    protected ?\Magento\Quote\Model\QuoteManagement $quoteManagement = null;
    protected ?\Magento\Quote\Api\CartRepositoryInterface $cartRepository = null;

    public function setUp(): void
    {
        parent::setUp();
        $this->objectManager = \Magento\TestFramework\ObjectManager::getInstance();
        $this->cart = $this->objectManager->get(\Magento\Checkout\Model\Cart::class);
        $this->productRepository = $this->objectManager->get(\Magento\Catalog\Api\ProductRepositoryInterface::class);
    }

    /**
     * @magentoAppArea frontend
     * @magentoAppIsolation enabled
     * @magentoDbIsolation enabled
     * @magentoDataFixture MageSuite_DailyDeal::Test/Integration/_files/products.php
     * @magentoConfigFixture current_store daily_deal/general/active 1
     * @magentoConfigFixture current_store daily_deal/general/use_qty_limitation 1
     */
    public function testItUpdatesCartCorrectly(): void
    {
        $product = $this->productRepository->get('actual_offer');
        $this->cart->addProduct($product, [
            'product' => $product->getId(),
            'qty' => 1
        ])->save();

        $items = $this->cart->getQuote()->getAllVisibleItems();
        foreach ($items as $item) {
            $this->assertEquals(20, $item->getProduct()->getPrice());
            $this->assertEquals(5, $item->getCustomPrice());
        }

        $product->setDailyDealPrice(4);
        $this->productRepository->save($product);

        $this->getRequest()->setMethod(\Magento\Framework\App\Request\Http::METHOD_GET);
        $this->dispatch('checkout/cart/index');

        $items = $this->cart->getQuote()->getAllVisibleItems();
        foreach ($items as $item) {
            $this->assertEquals(20, $item->getProduct()->getPrice());
            $this->assertEquals(4, $item->getCustomPrice());
        }
    }
}
