<?php

namespace MageSuite\DailyDeal\Helper;

class Configuration extends \Magento\Framework\App\Helper\AbstractHelper
{
    protected $config;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfigInterface
    ) {
        parent::__construct($context);

        $this->scopeConfig = $scopeConfigInterface;
    }

    public function isActive()
    {
        $config = $this->getConfig();

        return $config['active'] ? true : false;
    }

    public function isQtyLimitationEnabled()
    {
        $config = $this->getConfig();

        return ($config['active'] && $config['use_qty_limitation']) ? true : false;
    }

    public function displayOnTile()
    {
        $config = $this->getConfig();

        return $config['product_tile_display'];
    }

    private function getConfig()
    {
        if (!$this->config) {
            $this->config = $this->scopeConfig->getValue('daily_deal/general', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        }

        return $this->config;
    }
}
