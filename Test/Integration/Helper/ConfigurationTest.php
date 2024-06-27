<?php

namespace MageSuite\DailyDeal\Test\Integration\Helper;

/**
 * @magentoDbIsolation enabled
 */
class ConfigurationTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\TestFramework\ObjectManager
     */
    protected $objectManager;

    /**
     * @var \MageSuite\DailyDeal\Helper\Configuration
     */
    protected $configurationHelper;

    public function setUp(): void
    {
        $this->objectManager = \Magento\TestFramework\ObjectManager::getInstance();
        $this->configurationHelper = $this->objectManager->get(\MageSuite\DailyDeal\Helper\Configuration::class);
    }

    /**
     * @magentoDbIsolation enabled
     * @magentoConfigFixture current_store daily_deal/general/active 1
     * @magentoConfigFixture current_store daily_deal/general/use_qty_limitation 0
     * @magentoConfigFixture current_store daily_deal/general/product_tile_display badge
     * @magentoConfigFixture current_store daily_deal/general/allow_backorders 1
     */
    public function testItReturnsCorrectConfig()
    {
        $this->assertTrue($this->configurationHelper->isActive());
        $this->assertFalse($this->configurationHelper->isQtyLimitationEnabled());
        $this->assertEquals('badge', $this->configurationHelper->displayOnTile());
        $this->assertTrue($this->configurationHelper->isAllowBackOrdersEnabled());
    }
}
