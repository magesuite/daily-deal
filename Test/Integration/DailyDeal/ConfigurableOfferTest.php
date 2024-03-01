<?php

namespace MageSuite\DailyDeal\Test\Integration\DailyDeal;

class ConfigurableOfferTest extends \PHPUnit\Framework\TestCase
{
    protected ?\Magento\Checkout\Model\Cart $cart = null;
    protected ?\Magento\Framework\ObjectManagerInterface $objectManager = null;
    protected ?\MageSuite\DailyDeal\Service\OfferManager $offerManager = null;
    protected ?\MageSuite\DailyDeal\Model\ResourceModel\Offer $offerResource = null;
    protected ?\Magento\Catalog\Api\ProductRepositoryInterface $productRepository = null;
    protected ?\Magento\Quote\Model\QuoteManagement $quoteManagement = null;
    protected ?\Magento\Eav\Model\Config $eavConfig = null;

    public function setUp(): void
    {
        $this->objectManager = \Magento\TestFramework\ObjectManager::getInstance();
        $this->cart = $this->objectManager->get(\Magento\Checkout\Model\Cart::class);
        $this->offerManager = $this->objectManager->get(\MageSuite\DailyDeal\Service\OfferManager::class);
        $this->offerResource = $this->objectManager->get(\MageSuite\DailyDeal\Model\ResourceModel\Offer::class);
        $this->productRepository = $this->objectManager->get(\Magento\Catalog\Api\ProductRepositoryInterface::class);
        $this->quoteManagement = $this->objectManager->get(\Magento\Quote\Model\QuoteManagement::class);
        $this->eavConfig = $this->objectManager->get(\Magento\Eav\Model\Config::class);
    }

    public function tearDown(): void
    {
        $this->cart->truncate()->save();
    }

    /**
     * @magentoAppArea frontend
     * @magentoDbIsolation disabled
     * @magentoDataFixture MageSuite_DailyDeal::Test/Integration/_files/configurable_products.php
     * @magentoDataFixture Magento_InventoryIndexer::Test/_files/reindex_inventory.php
     * @magentoConfigFixture current_store daily_deal/general/active 1
     * @magentoConfigFixture current_store daily_deal/general/use_qty_limitation 1
     */
    public function testItAddsConfigurableProductWithCorrectValues(): void
    {
        $product = $this->productRepository->get('configurable_12345');
        $attribute = $this->eavConfig->getAttribute(\Magento\Catalog\Model\Product::ENTITY, 'test_configurable');
        $options = $attribute->getOptions();

        $this->cart->addProduct($product, [
            'product' => $product->getId(),
            'super_attribute' => [
                $attribute->getId() => $options[1]->getValue()
            ],
            'qty' => 1
        ]);

        $items = $this->cart->getQuote()->getAllItems();
        foreach ($items as $item) {
            if ($item->getProductType() !== \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE) {
                continue;
            }

            $this->assertEquals(10, $item->getProduct()->getPrice());
            $this->assertEquals(5, $item->getCustomPrice());

            $option = $item->getOptionByCode(\MageSuite\DailyDeal\Service\OfferManager::ITEM_OPTION_DD_OFFER);
            $this->assertNotNull($option);
            $this->assertTrue((boolean)$option->getValue());

            $buyRequest = $item->getOptionByCode('info_buyRequest');
            $this->assertArrayHasKey(\MageSuite\DailyDeal\Service\OfferManager::ITEM_OPTION_DD_OFFER, $buyRequest);
            $this->assertTrue($buyRequest[\MageSuite\DailyDeal\Service\OfferManager::ITEM_OPTION_DD_OFFER]);
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
    public function testItAddsConfigurableProductWithSpecialPrice(): void
    {
        $product = $this->productRepository->get('configurable_12345');
        $offerPrice = $this->offerManager->getOfferPrice($product);
        $this->assertEquals(5, $offerPrice);

        $attribute = $this->eavConfig->getAttribute(\Magento\Catalog\Model\Product::ENTITY, 'test_configurable');
        $options = $attribute->getOptions();

        $this->cart->addProduct($product, [
            'product' => $product->getId(),
            'super_attribute' => [
                $attribute->getId() => $options[2]->getValue()
            ],
            'qty' => 1
        ]);

        $items = $this->cart->getQuote()->getAllItems();
        foreach ($items as $item) {
            if ($item->getProductType() !== \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE) {
                $this->assertEquals(7, $item->getProduct()->getSpecialPrice());
                continue;
            }

            $this->assertEquals(10, $item->getProduct()->getPrice());
            $this->assertEquals(5, $item->getCustomPrice());
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
    public function testItDecreaseOfferUsage()
    {
        $product = $this->productRepository->get('configurable_12345');

        $this->assertEquals(50, $product->getDailyDealLimit());
        $this->offerManager->decreaseOfferLimit($product, 15);

        $product = $this->productRepository->get('configurable_12345');
        $this->assertEquals(35, $product->getDailyDealLimit());
    }

    /**
     * @magentoAppArea frontend
     * @magentoDbIsolation disabled
     * @magentoDataFixture MageSuite_DailyDeal::Test/Integration/_files/configurable_products.php
     * @magentoDataFixture Magento_InventoryIndexer::Test/_files/reindex_inventory.php
     * @magentoConfigFixture current_store daily_deal/general/active 1
     * @magentoConfigFixture current_store daily_deal/general/use_qty_limitation 1
     */
    public function testItDecreaseOfferUsageAfterCreateOrderConfigurable(): void
    {
        $qty = 2;
        $product = $this->productRepository->get('configurable_12345');
        $this->assertEquals(50, $product->getDailyDealLimit());

        $attribute = $this->eavConfig->getAttribute(\Magento\Catalog\Model\Product::ENTITY, 'test_configurable');
        $options = $attribute->getOptions();

        $quoteOptions = [
            'product' => $product->getId(),
            'super_attribute' => [
                $attribute->getId() => $options[2]->getValue()
            ],
            'qty' => $qty
        ];

        $quote = $this->prepareQuote($product, $quoteOptions);
        $this->quoteManagement->submit($quote);

        $product = $this->productRepository->get('configurable_12345');
        $this->assertEquals(48, $product->getDailyDealLimit());
    }

    protected function prepareQuote($product, $options): \Magento\Quote\Api\Data\CartInterface
    {
        $this->cart->addProduct($product, $options);

        $addressData = [
            'region' => 'CA',
            'postcode' => '11111',
            'lastname' => 'lastname',
            'firstname' => 'firstname',
            'street' => 'street',
            'city' => 'Los Angeles',
            'email' => 'admin@example.com',
            'telephone' => '11111111',
            'country_id' => 'US'
        ];

        $shippingMethod = 'freeshipping_freeshipping';

        $billingAddress = $this->objectManager->create('Magento\Quote\Api\Data\AddressInterface', ['data' => $addressData]);
        $billingAddress->setAddressType('billing');

        $shippingAddress = clone $billingAddress;
        $shippingAddress->setId(null)->setAddressType('shipping');

        $rate = $this->objectManager->create(\Magento\Quote\Model\Quote\Address\Rate::class);
        $rate->setCode($shippingMethod);

        $shippingAddress->setShippingMethod($shippingMethod);
        $shippingAddress->setShippingRate($rate);

        $quote = $this->cart->getQuote();
        $quote->setBillingAddress($billingAddress);
        $quote->setShippingAddress($shippingAddress);
        $quote->getShippingAddress()->addShippingRate($rate);

        $payment = $quote->getPayment();
        $payment->setMethod('checkmo');
        $quote->setPayment($payment);

        $quote->setCustomerEmail('test@example.com');
        $quote->setCustomerIsGuest(true);

        $quote->collectTotals();

        return $quote;
    }
}
