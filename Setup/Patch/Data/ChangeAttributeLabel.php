<?php

declare(strict_types=1);

namespace MageSuite\DailyDeal\Setup\Patch\Data;

class ChangeAttributeLabel implements \Magento\Framework\Setup\Patch\DataPatchInterface
{
    public function __construct(
        protected \Magento\Eav\Setup\EavSetup $eavSetup,
    ) {}

    public static function getDependencies(): array
    {
        return [];
    }

    public function getAliases(): array
    {
        return [];
    }

    public function apply(): void
    {
        $this->eavSetup->updateAttribute(\Magento\Catalog\Model\Product::ENTITY, 'daily_deal_enabled', 'frontend_label', 'Daily Deal Enabled');
    }
}
