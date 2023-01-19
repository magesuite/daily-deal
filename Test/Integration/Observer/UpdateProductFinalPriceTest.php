<?php

namespace MageSuite\DailyDeal\Test\Integration\Observer;

class UpdateProductFinalPriceTest extends AbstractUpdateProductFinalPrice
{
    /**
     * @magentoDataFixture MageSuite_DailyDeal::Test/Integration/_files/simple_product_with_dd.php
     * @magentoConfigFixture current_store daily_deal/general/active 1
     * @magentoConfigFixture current_store catalog/price/scope 1
     * @magentoAppIsolation enabled
     * @magentoDbIsolation enabled
     * @magentoAppArea frontend
     */
    public function testTileSimpleProductPrice()
    {
        $this->runTileSimpleProductPriceTest();
    }
}
