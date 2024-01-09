<?php

$objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();
$productRepository = $objectManager->create(\Magento\Catalog\Api\ProductRepositoryInterface::class);
$product = $objectManager->create('Magento\Catalog\Model\Product');

$product->setTypeId(\Magento\Catalog\Model\Product\Type::TYPE_SIMPLE)
    ->setId(600)
    ->setAttributeSetId(4)
    ->setName('Active offer')
    ->setSku('active_offer')
    ->setUrlKey('active_offer')
    ->setPrice(10)
    ->setVisibility(\Magento\Catalog\Model\Product\Visibility::VISIBILITY_BOTH)
    ->setStatus(\Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED)
    ->setWebsiteIds([1])
    ->setStockData(['use_config_manage_stock' => 1, 'qty' => 100, 'is_qty_decimal' => 0, 'is_in_stock' => 1])
    ->setCanSaveCustomOptions(true)
    ->setSpecialPrice(7)
    ->setDailyDealEnabled(1)
    ->setDailyDealLimit(50)
    ->setDailyDealInitialAmount(60)
    ->setDailyDealFrom('2018-03-19 00:00:00')
    ->setDailyDealTo('2031-03-22 08:00:00')
    ->setDailyDealPrice(5)
    ->save();

$product = $objectManager->create('Magento\Catalog\Model\Product');

$product->setTypeId(\Magento\Catalog\Model\Product\Type::TYPE_SIMPLE)
    ->setId(601)
    ->setAttributeSetId(4)
    ->setName('Old offer')
    ->setSku('old_offer')
    ->setUrlKey('old_offer')
    ->setPrice(10)
    ->setVisibility(\Magento\Catalog\Model\Product\Visibility::VISIBILITY_BOTH)
    ->setStatus(\Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED)
    ->setWebsiteIds([1])
    ->setStockData(['use_config_manage_stock' => 1, 'qty' => 2, 'is_qty_decimal' => 0, 'is_in_stock' => 1])
    ->setCanSaveCustomOptions(true)
    ->setDailyDealEnabled(1)
    ->setDailyDealLimit(20)
    ->setDailyDealFrom('2018-03-11 00:00:00')
    ->setDailyDealTo('2018-03-16 08:00:00')
    ->setDailyDealPrice(1)
    ->save();

$product = $objectManager->create('Magento\Catalog\Model\Product');

$product->setTypeId(\Magento\Catalog\Model\Product\Type::TYPE_SIMPLE)
    ->setId(602)
    ->setAttributeSetId(4)
    ->setName('No offer')
    ->setSku('no_offer')
    ->setUrlKey('no_offer')
    ->setPrice(10)
    ->setVisibility(\Magento\Catalog\Model\Product\Visibility::VISIBILITY_BOTH)
    ->setStatus(\Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED)
    ->setWebsiteIds([1])
    ->setStockData(['use_config_manage_stock' => 1, 'qty' => 2, 'is_qty_decimal' => 0, 'is_in_stock' => 1])
    ->setCanSaveCustomOptions(true)
    ->save();

$product = $objectManager->create('Magento\Catalog\Model\Product');

$product->setTypeId(\Magento\Catalog\Model\Product\Type::TYPE_SIMPLE)
    ->setId(603)
    ->setAttributeSetId(4)
    ->setName('New offer')
    ->setSku('new_offer')
    ->setUrlKey('new_offer')
    ->setPrice(10)
    ->setVisibility(\Magento\Catalog\Model\Product\Visibility::VISIBILITY_BOTH)
    ->setStatus(\Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED)
    ->setWebsiteIds([1])
    ->setStockData(['use_config_manage_stock' => 1, 'qty' => 2, 'is_qty_decimal' => 0, 'is_in_stock' => 1])
    ->setCanSaveCustomOptions(true)
    ->setDailyDealEnabled(0)
    ->setDailyDealLimit(20)
    ->setDailyDealFrom('2018-03-14 00:00:00')
    ->setDailyDealTo('2031-03-25 08:00:00')
    ->setDailyDealPrice(3)
    ->save();

$product = $objectManager->create('Magento\Catalog\Model\Product');

$product->setTypeId(\Magento\Catalog\Model\Product\Type::TYPE_SIMPLE)
    ->setId(605)
    ->setAttributeSetId(4)
    ->setName('Offer with smaller qty')
    ->setSku('smaller_qty')
    ->setUrlKey('smaller_qty')
    ->setPrice(20)
    ->setVisibility(\Magento\Catalog\Model\Product\Visibility::VISIBILITY_BOTH)
    ->setStatus(\Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED)
    ->setWebsiteIds([1])
    ->setStockData(['use_config_manage_stock' => 1, 'qty' => 10, 'is_qty_decimal' => 0, 'is_in_stock' => 1])
    ->setCanSaveCustomOptions(true)
    ->setDailyDealEnabled(1)
    ->setDailyDealLimit(20)
    ->setDailyDealFrom('2000-03-14 00:00:00')
    ->setDailyDealTo('2035-03-25 08:00:00')
    ->setDailyDealPrice(5)
    ->save();


/** @var  \Magento\Catalog\Api\Data\ProductLinkInterface $productLinks */
$productLinks = $objectManager->create('Magento\Catalog\Api\Data\ProductLinkInterface');
$linkData = $productLinks
    ->setSku('actual_offer')
    ->setLinkedProductSku('smaller_qty')
    ->setLinkType("related");
$relatedProducts[] = $linkData;

$product = $objectManager->create('Magento\Catalog\Model\Product');

$originalTimezone = date_default_timezone_get();
date_default_timezone_set('UTC');
$currentUtcTime = date('Y-m-d H:i:s');
$oneHourLater = date('Y-m-d H:i:s', strtotime('+1 hour'));
date_default_timezone_set($originalTimezone);

$product->setTypeId(\Magento\Catalog\Model\Product\Type::TYPE_SIMPLE)
    ->setId(604)
    ->setAttributeSetId(4)
    ->setName('Actual offer')
    ->setSku('actual_offer')
    ->setUrlKey('actual_offer')
    ->setPrice(20)
    ->setVisibility(\Magento\Catalog\Model\Product\Visibility::VISIBILITY_BOTH)
    ->setStatus(\Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED)
    ->setWebsiteIds([1])
    ->setStockData(['use_config_manage_stock' => 1, 'qty' => 100, 'is_qty_decimal' => 0, 'is_in_stock' => 1])
    ->setCanSaveCustomOptions(true)
    ->setDailyDealEnabled(1)
    ->setDailyDealLimit(20)
    ->setDailyDealFrom($currentUtcTime)
    ->setDailyDealTo($oneHourLater)
    ->setDailyDealPrice(5)
    ->setProductLinks($relatedProducts)
    ->save();

$product = $objectManager->create('Magento\Catalog\Model\Product');

$product->setTypeId(\Magento\Catalog\Model\Product\Type::TYPE_SIMPLE)
    ->setId(606)
    ->setAttributeSetId(4)
    ->setName('Offer with special price')
    ->setSku('offer_with_special_price')
    ->setUrlKey('offer_with_special_price')
    ->setPrice(20)
    ->setVisibility(\Magento\Catalog\Model\Product\Visibility::VISIBILITY_BOTH)
    ->setStatus(\Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED)
    ->setWebsiteIds([1])
    ->setStockData(['use_config_manage_stock' => 1, 'qty' => 10, 'is_qty_decimal' => 0, 'is_in_stock' => 1])
    ->setCanSaveCustomOptions(true)
    ->setSpecialPrice(5)
    ->setDailyDealEnabled(1)
    ->setDailyDealLimit(20)
    ->setDailyDealFrom('2000-03-14 00:00:00')
    ->setDailyDealTo('2035-03-25 08:00:00')
    ->setDailyDealPrice(10)
    ->save();


$bundleProduct = $objectManager->create(\Magento\Catalog\Model\Product::class);
$bundleProduct->setTypeId('bundle')
    ->setId(607)
    ->setAttributeSetId(4)
    ->setWeight(2)
    ->setWebsiteIds([1])
    ->setName('Bundle Product')
    ->setSku('bundle-product')
    ->setVisibility(\Magento\Catalog\Model\Product\Visibility::VISIBILITY_BOTH)
    ->setStatus(\Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED)
    ->setStockData(['use_config_manage_stock' => 1, 'qty' => 100, 'is_qty_decimal' => 0, 'is_in_stock' => 1])
    ->setPriceView(1)
    ->setSkuType(1)
    ->setWeightType(1)
    ->setPriceType(1)
    ->setShipmentType(0)
    ->setPrice(10.0)
    ->setDailyDealEnabled(1)
    ->setDailyDealLimit(50)
    ->setDailyDealInitialAmount(60)
    ->setDailyDealFrom('2018-03-19 00:00:00')
    ->setDailyDealTo('2031-03-22 08:00:00')
    ->setDailyDealPrice(5)
    ->setBundleOptionsData(
        [
            [
                'title' => 'Bundle Product Items',
                'default_title' => 'Bundle Product Items',
                'type' => 'select', 'required' => 1,
                'delete' => '',
            ],
        ]
    )
    ->setBundleSelectionsData(
        [
            [
                [
                    'product_id' => $product->getId(),
                    'selection_price_value' => 2.75,
                    'selection_qty' => 1,
                    'selection_can_change_qty' => 1,
                    'delete' => '',

                ],
            ],
        ]
    );

if ($bundleProduct->getBundleOptionsData()) {
    $options = [];
    foreach ($bundleProduct->getBundleOptionsData() as $key => $optionData) {
        if (!(bool)$optionData['delete']) {
            $option = $objectManager->create(\Magento\Bundle\Api\Data\OptionInterfaceFactory::class)
                ->create(['data' => $optionData]);
            $option->setSku($bundleProduct->getSku());
            $option->setOptionId(null);

            $links = [];
            $bundleLinks = $bundleProduct->getBundleSelectionsData();
            if (!empty($bundleLinks[$key])) {
                foreach ($bundleLinks[$key] as $linkData) {
                    if (!(bool)$linkData['delete']) { // phpcs:ignore
                        /** @var \Magento\Bundle\Api\Data\LinkInterface $link */
                        $link = $objectManager->create(\Magento\Bundle\Api\Data\LinkInterfaceFactory::class)
                            ->create(['data' => $linkData]);
                        $linkProduct = $productRepository->getById($linkData['product_id']);
                        $link->setSku($linkProduct->getSku());
                        $link->setQty($linkData['selection_qty']);
                        $link->setPrice($linkData['selection_price_value']);
                        if (isset($linkData['selection_can_change_qty'])) {  // phpcs:ignore
                            $link->setCanChangeQuantity($linkData['selection_can_change_qty']);
                        }
                        $links[] = $link;
                    }
                }
                $option->setProductLinks($links);
                $options[] = $option;
            }
        }
    }
    $extension = $product->getExtensionAttributes();
    $extension->setBundleProductOptions($options);
    $bundleProduct->setExtensionAttributes($extension);
}

$productRepository->save($bundleProduct, true);

$product = $objectManager->create('Magento\Catalog\Model\Product');

$product->setTypeId(\Magento\Catalog\Model\Product\Type::TYPE_SIMPLE)
    ->setId(608)
    ->setAttributeSetId(4)
    ->setName('Active offer low stock')
    ->setSku('active_offer_low_stock')
    ->setUrlKey('active_offer_low_stock')
    ->setPrice(10)
    ->setVisibility(\Magento\Catalog\Model\Product\Visibility::VISIBILITY_BOTH)
    ->setStatus(\Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED)
    ->setWebsiteIds([1])
    ->setStockData(['use_config_manage_stock' => 1, 'qty' => 5, 'is_qty_decimal' => 0, 'is_in_stock' => 1])
    ->setCanSaveCustomOptions(true)
    ->setSpecialPrice(7)
    ->setDailyDealEnabled(1)
    ->setDailyDealLimit(50)
    ->setDailyDealInitialAmount(60)
    ->setDailyDealFrom('2018-03-19 00:00:00')
    ->setDailyDealTo('2031-03-22 08:00:00')
    ->setDailyDealPrice(5)
    ->save();

$product = $objectManager->create('Magento\Catalog\Model\Product');

$product->setTypeId(\Magento\Catalog\Model\Product\Type::TYPE_SIMPLE)
    ->setId(609)
    ->setAttributeSetId(4)
    ->setName('Active offer ouf of stock')
    ->setSku('active_offer_out_of_stock')
    ->setUrlKey('active_offer_out_of_stock')
    ->setPrice(10)
    ->setVisibility(\Magento\Catalog\Model\Product\Visibility::VISIBILITY_BOTH)
    ->setStatus(\Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED)
    ->setWebsiteIds([1])
    ->setStockData(['use_config_manage_stock' => 1, 'qty' => 5, 'is_qty_decimal' => 0, 'is_in_stock' => 0])
    ->setCanSaveCustomOptions(true)
    ->setSpecialPrice(7)
    ->setDailyDealEnabled(1)
    ->setDailyDealLimit(50)
    ->setDailyDealInitialAmount(60)
    ->setDailyDealFrom('2018-03-19 00:00:00')
    ->setDailyDealTo('2031-03-22 08:00:00')
    ->setDailyDealPrice(5)
    ->save();
