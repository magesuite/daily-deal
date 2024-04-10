<?php

\Magento\TestFramework\Workaround\Override\Fixture\Resolver::getInstance()
    ->requireDataFixture('Magento/ConfigurableProduct/_files/configurable_attribute.php');

$objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();

/** @var \Magento\Catalog\Api\ProductRepositoryInterface $productRepository */
$productRepository = $objectManager->get(\Magento\Catalog\Api\ProductRepositoryInterface::class);

/** @var $installer \Magento\Catalog\Setup\CategorySetup */
$installer = $objectManager->create(\Magento\Catalog\Setup\CategorySetup::class);
$attributeSetId = $installer->getAttributeSetId('catalog_product', 'Default');

/** @var Magento\Eav\Model\Config $eavConfig */
$eavConfig = $objectManager->get(\Magento\Eav\Model\Config::class);
$attribute = $eavConfig->getAttribute(\Magento\Catalog\Model\Product::ENTITY, 'test_configurable');

/** @var \Magento\Eav\Api\Data\AttributeOptionInterface[] $options */
$options = $attribute->getOptions();
array_shift($options); //remove the first option which is empty
$options = array_slice($options, 0, 2);

$associatedProductIds = [];
$productIds = [10, 20];

$attributeValues = [];
foreach ($options as $option) {
    /** @var $product Product */
    $product = $objectManager->create(\Magento\Catalog\Model\Product::class);
    $productId = array_shift($productIds);
    $product->setTypeId(\Magento\Catalog\Model\Product\Type::TYPE_SIMPLE)
        ->setId($productId)
        ->setAttributeSetId($attributeSetId)
        ->setWebsiteIds([1])
        ->setName('Configurable Option' . $option->getLabel())
        ->setSku('simple_' . $productId)
        ->setPrice(10)
        ->setTestConfigurable($option->getValue())
        ->setVisibility(\Magento\Catalog\Model\Product\Visibility::VISIBILITY_NOT_VISIBLE)
        ->setStatus(\Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED)
        ->setStockData(['use_config_manage_stock' => 1, 'qty' => 100, 'is_qty_decimal' => 0, 'is_in_stock' => 1]);
    $product = $productRepository->save($product);

    $attributeValues[] = [
        'label' => 'test',
        'attribute_id' => $attribute->getId(),
        'value_index' => $option->getValue(),
    ];
    $associatedProductIds[] = $product->getId();
}

/** @var $product \Magento\Catalog\Model\Product */
$product = $objectManager->create(\Magento\Catalog\Model\Product::class);
/** @var \Magento\ConfigurableProduct\Helper\Product\Options\Factory $optionsFactory */
$optionsFactory = $objectManager->create(\Magento\ConfigurableProduct\Helper\Product\Options\Factory::class);
$configurableAttributesData = [
    [
        'attribute_id' => $attribute->getId(),
        'code' => $attribute->getAttributeCode(),
        'label' => $attribute->getStoreLabel(),
        'position' => '0',
        'values' => $attributeValues,
    ],
];
$configurableOptions = $optionsFactory->create($configurableAttributesData);
$extensionConfigurableAttributes = $product->getExtensionAttributes();
$extensionConfigurableAttributes->setConfigurableProductOptions($configurableOptions);
$extensionConfigurableAttributes->setConfigurableProductLinks($associatedProductIds);
$product->setExtensionAttributes($extensionConfigurableAttributes);

$product->setTypeId(\Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE)
    ->setId(1)
    ->setAttributeSetId($attributeSetId)
    ->setWebsiteIds([1])
    ->setName('Configurable Product')
    ->setSku('configurable')
    ->setVisibility(\Magento\Catalog\Model\Product\Visibility::VISIBILITY_BOTH)
    ->setStatus(\Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED)
    ->setStockData(['use_config_manage_stock' => 1, 'is_in_stock' => 1]);
$productRepository->cleanCache();
$productRepository->save($product);

/* Create simple products per each option value*/
/** @var \Magento\Eav\Api\Data\AttributeOptionInterface[] $options */
$options = $attribute->getOptions();

$attributeValues = [];
$attributeSetId = $installer->getAttributeSetId('catalog_product', 'Default');
$associatedProductIds = [];
$productIds = [30, 40];
array_shift($options); //remove the first option which is empty

foreach ($options as $key => $option) {
    /** @var $product \Magento\Catalog\Model\Product */
    $product = $objectManager->create(\Magento\Catalog\Model\Product::class);
    $productId = array_shift($productIds);
    $product->setTypeId(\Magento\Catalog\Model\Product\Type::TYPE_SIMPLE)
        ->setId($productId)
        ->setAttributeSetId($attributeSetId)
        ->setWebsiteIds([1])
        ->setName('Configurable Option' . $option->getLabel())
        ->setSku('simple_' . $productId)
        ->setPrice(10)
        ->setTestConfigurable($option->getValue())
        ->setVisibility(\Magento\Catalog\Model\Product\Visibility::VISIBILITY_NOT_VISIBLE)
        ->setStatus(\Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED)
        ->setStockData(['use_config_manage_stock' => 1, 'qty' => 100, 'is_qty_decimal' => 0, 'is_in_stock' => 1]);

    if ($option->getLabel() == 'Option 2') {
        $product->setSpecialPrice(7);
    }

    $product = $productRepository->save($product);

    $attributeValues[] = [
        'label' => 'test',
        'attribute_id' => $attribute->getId(),
        'value_index' => $option->getValue(),
    ];
    $associatedProductIds[] = $product->getId();
}

/** @var $product \Magento\Catalog\Model\Product */
$product = $objectManager->create(\Magento\Catalog\Model\Product::class);

/** @var \Magento\ConfigurableProduct\Helper\Product\Options\Factory $optionsFactory */
$optionsFactory = $objectManager->create(\Magento\ConfigurableProduct\Helper\Product\Options\Factory::class);

$configurableAttributesData = [
    [
        'attribute_id' => $attribute->getId(),
        'code' => $attribute->getAttributeCode(),
        'label' => $attribute->getStoreLabel(),
        'position' => '0',
        'values' => $attributeValues,
    ],
];

$configurableOptions = $optionsFactory->create($configurableAttributesData);

$extensionConfigurableAttributes = $product->getExtensionAttributes();
$extensionConfigurableAttributes->setConfigurableProductOptions($configurableOptions);
$extensionConfigurableAttributes->setConfigurableProductLinks($associatedProductIds);

$product->setExtensionAttributes($extensionConfigurableAttributes);

$product->setTypeId(\Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE)
    ->setId(11)
    ->setAttributeSetId($attributeSetId)
    ->setWebsiteIds([1])
    ->setName('Configurable Product 12345')
    ->setSku('configurable_12345')
    ->setVisibility(\Magento\Catalog\Model\Product\Visibility::VISIBILITY_BOTH)
    ->setStatus(\Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED)
    ->setStockData(['use_config_manage_stock' => 1, 'is_in_stock' => 1])
    ->setDailyDealEnabled(1)
    ->setDailyDealLimit(50)
    ->setDailyDealInitialAmount(60)
    ->setDailyDealFrom('2018-03-19 00:00:00')
    ->setDailyDealTo('2031-03-22 08:00:00')
    ->setDailyDealPrice(5)
    ->save();
$productRepository->cleanCache();
$productRepository->save($product);

$product->reindex();
$product->priceReindexCallback();
