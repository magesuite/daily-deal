<?php

namespace MageSuite\DailyDeal\Model\Config\Source\ProductTile;

class DisplayType implements \Magento\Framework\Option\ArrayInterface
{
    const OFFER_DISPLAY_TYPE_NONE = 'none';
    const OFFER_DISPLAY_TYPE_BADGE = 'badge';
    const OFFER_DISPLAY_TYPE_BADGE_COUNTER = 'badge_counter';

    public function toOptionArray()
    {
        return [
            ['value' => self::OFFER_DISPLAY_TYPE_NONE, 'label' => 'None'],
            ['value' => self::OFFER_DISPLAY_TYPE_BADGE, 'label' => 'Compact (as badge)'],
            ['value' => self::OFFER_DISPLAY_TYPE_BADGE_COUNTER, 'label' => 'Full (with countdown)'],
        ];
    }
}
