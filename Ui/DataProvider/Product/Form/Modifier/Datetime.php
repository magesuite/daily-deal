<?php

namespace MageSuite\DailyDeal\Ui\DataProvider\Product\Form\Modifier;

class Datetime extends \Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\AbstractModifier
{
    /**
     * @var \Magento\Framework\Stdlib\ArrayManager
     */
    protected $arrayManager;

    public function __construct(
        \Magento\Framework\Stdlib\ArrayManager $arrayManager
    ) {
        $this->arrayManager = $arrayManager;
    }

    public function modifyMeta(array $meta)
    {
        $meta = $this->enableTime($meta);

        return $meta;
    }

    public function modifyData(array $data)
    {
        return $data;
    }

    protected function enableTime($meta)
    {
        $fields = ['daily_deal_from', 'daily_deal_to'];

        foreach ($fields as $field) {

            $elementPath = $this->arrayManager->findPath($field, $meta, null, 'children');
            $containerPath = $this->arrayManager->findPath(self::CONTAINER_PREFIX . $field, $meta, null, 'children');

            if (!$elementPath) {
                continue;
            }

            $meta = $this->arrayManager->merge(
                $containerPath,
                $meta,
                [
                    'children'  => [
                        $field => [
                            'arguments' => [
                                'data' => [
                                    'config' => [
                                        'default' => '',
                                        'options'       => [
                                            'dateFormat' > 'Y-m-d',
                                            'timeFormat' => 'HH:mm',
                                            'showsTime' => true
                                        ]
                                    ],
                                ],
                            ],
                        ]
                    ]
                ]
            );
        }

        return $meta;
    }
}
