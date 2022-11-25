<?php

namespace MageSuite\DailyDeal\Test\Integration\Observer;

class UpdateProductFinalPriceTest extends AbstractUpdateProductFinalPrice
{
    /**
     * @magentoDataFixtureBeforeTransaction MageSuite_DailyDeal::Test/Integration/_files/simple_product_with_dd.php
     * @magentoConfigFixture current_store daily_deal/general/active 1
     * @magentoAppArea frontend
     */
    public function testTileSimpleProductPrice()
    {
        $this->runTileSimpleProductPriceTest();
    }
}
