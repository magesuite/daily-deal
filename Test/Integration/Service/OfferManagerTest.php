<?php

declare(strict_types=1);

namespace MageSuite\DailyDeal\Test\Integration\Service;

/**
 * @magentoAppArea frontend
 * @magentoAppIsolation enabled
 * @magentoDbIsolation enabled
 * @magentoDataFixture MageSuite_DailyDeal::Test/Integration/_files/products.php
 */
class OfferManagerTest extends \PHPUnit\Framework\TestCase
{
    protected \Magento\Framework\App\ObjectManager $objectManager;
    protected \Magento\Catalog\Api\ProductRepositoryInterface $productRepository;
    protected \MageSuite\DailyDeal\Service\OfferManager $offerManager;
    protected \MageSuite\DailyDeal\Model\ResourceModel\Offer $offerResource;

    public function setUp(): void
    {
        $this->objectManager = \Magento\TestFramework\ObjectManager::getInstance();
        $this->productRepository = $this->objectManager->get(\Magento\Catalog\Api\ProductRepositoryInterface::class);
        $this->offerManager = $this->objectManager->get(\MageSuite\DailyDeal\Service\OfferManager::class);
        $this->offerResource = $this->objectManager->get(\MageSuite\DailyDeal\Model\ResourceModel\Offer::class);
    }

    /**
     * @magentoConfigFixture current_store daily_deal/general/active 1
     * @magentoConfigFixture current_store daily_deal/general/use_qty_limitation 1
     */
    public function testItReturnsCorrectOffers(): void
    {
        $offers = $this->offerManager->getOffers();

        $offersArray = [];
        foreach ($offers as $offer) {
            $offersArray[] = $offer;
        }

        $this->assertCount(10, $offersArray);
        $this->assertEquals(600, $offersArray[0]->getId());
        $this->assertEquals(601, $offersArray[1]->getId());

        $this->assertEquals('2018-03-19 00:00:00', $offersArray[0]->getDailyDealFrom());
        $this->assertEquals('2031-03-22 08:00:00', $offersArray[0]->getDailyDealTo());
        $this->assertEquals(20, $offersArray[1]->getDailyDealLimit());
        $this->assertEquals(1, $offersArray[1]->getDailyDealEnabled());
    }

    /**
     * @magentoConfigFixture current_store daily_deal/general/active 1
     * @magentoConfigFixture current_store daily_deal/general/use_qty_limitation 1
     */
    public function testItReturnsCorrectData(): void
    {
        $date = new \DateTime('2018-03-20 01:00:00');
        $storeId = 1;
        $qtyLimitation = 1;

        $this->offerManager->setTimestamp($date->getTimestamp());
        $this->offerManager->setStoreId($storeId);

        $offers = $this->offerManager->getOffers();
        $offersArray = [];

        foreach ($offers as $offer) {
            $offersArray[] = $offer;
        }

        $this->assertNull($this->offerManager->getOfferAction($offersArray[0], $qtyLimitation, $storeId));

        $this->assertEquals(
            \MageSuite\DailyDeal\Service\OfferManager::TYPE_REMOVE,
            $this->offerManager->getOfferAction($offersArray[1], $qtyLimitation, $storeId)
        );

        $this->assertEquals(
            \MageSuite\DailyDeal\Service\OfferManager::TYPE_ADD,
            $this->offerManager->getOfferAction($offersArray[2], $qtyLimitation, $storeId)
        );

        $this->assertEquals(1, $offersArray[1]->getDailyDealEnabled());

        $this->offerManager->applyAction($offersArray[1], \MageSuite\DailyDeal\Service\OfferManager::TYPE_REMOVE);
        $product = $this->productRepository->get($offersArray[1]->getSku());

        $this->assertEquals(0, $product->getDailyDealEnabled());

        $this->assertEquals(0, $offersArray[2]->getDailyDealEnabled());

        $this->offerManager->applyAction($offersArray[2], \MageSuite\DailyDeal\Service\OfferManager::TYPE_ADD);
        $product = $this->productRepository->get($offersArray[2]->getSku());

        $this->assertEquals(1, $product->getDailyDealEnabled());
    }

    /**
     * @magentoConfigFixture current_store daily_deal/general/active 1
     * @magentoConfigFixture current_store daily_deal/general/use_qty_limitation 1
     * @magentoConfigFixture current_store daily_deal/general/allow_backorders 1
     */
    public function testItReturnsCorrectDataForBackorders(): void
    {
        $date = new \DateTime('2018-03-20 01:00:00');
        $storeId = 1;

        $this->offerManager->setTimestamp($date->getTimestamp());
        $this->offerManager->setStoreId($storeId);

        $offers = $this->offerManager->getOffers();

        foreach ($offers as $offer) {
            $product = $this->productRepository->get($offer->getSku());
            $productBackorders = $product->getExtensionAttributes()->getStockItem()->getBackorders();
            $this->assertEquals($productBackorders, $offer->getBackorders());
        }
    }

    /**
     * @magentoConfigFixture current_store daily_deal/general/active 1
     * @magentoConfigFixture current_store daily_deal/general/use_qty_limitation 1
     * @magentoConfigFixture current_store daily_deal/general/allow_backorders 0
     */
    public function testItReturnsNoBackordersWhenDisabled(): void
    {
        $date = new \DateTime('2018-03-20 01:00:00');
        $storeId = 1;

        $this->offerManager->setTimestamp($date->getTimestamp());
        $this->offerManager->setStoreId($storeId);

        $offers = $this->offerManager->getOffers();

        foreach ($offers as $offer) {
            $this->assertNull($offer->getBackorders());
        }
    }

    /**
     * @magentoConfigFixture current_store daily_deal/general/active 1
     * @magentoConfigFixture current_store daily_deal/general/use_qty_limitation 1
     */
    public function testItCorrectlyValidatesOfferWithAndWithoutLimit(): void
    {
        $product = $this->productRepository->get('active_offer');
        $this->assertTrue($this->offerManager->validateOfferInQuote($product, 10));

        $product->setDailyDealLimit(null);
        $this->assertTrue($this->offerManager->validateOfferInQuote($product, 10));

        $product->setDailyDealLimit(0);
        $this->assertFalse($this->offerManager->validateOfferInQuote($product, 10));
    }
}
