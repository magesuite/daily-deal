<?php

declare(strict_types=1);

/** @var \Magento\Framework\ObjectManagerInterface $objectManager */
$objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();

/** @var \Magento\Framework\App\ProductMetadataInterface $productMetadata */
$productMetadata = $objectManager->get(\Magento\Framework\App\ProductMetadataInterface::class);

\Magento\TestFramework\Workaround\Override\Fixture\Resolver::getInstance()
    ->requireDataFixture('Magento/Catalog/_files/products_new.php');

$productRepository = $objectManager->get(\Magento\Catalog\Api\ProductRepositoryInterface::class);

/** @var \Magento\Catalog\Api\ProductRepositoryInterface $product */
$product = $productRepository->get('simple');

$product
    ->setDailyDealEnabled(0)
    ->setDailyDealLimit(50)
    ->setDailyDealInitialAmount(60)
    ->setDailyDealFrom(strtotime('-10 days'))
    ->setDailyDealTo(strtotime('+10 days'))
    ->setDailyDealPrice(2.30);

$productRepository->save($product);

$offerManager = $objectManager->create(\MageSuite\DailyDeal\Service\OfferManagerInterface::class);
$storeManager = $objectManager->get(\Magento\Store\Model\StoreManagerInterface::class);

$stores = $storeManager->getStores(true);
$storeIds = array_keys($stores);
sort($storeIds);

foreach ($storeIds as $storeId) {
    $offerManager->refreshOffers($storeId);
}
