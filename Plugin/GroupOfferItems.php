<?php

namespace MageSuite\DailyDeal\Plugin;

class GroupOfferItems
{
    /**
     * @var \MageSuite\DailyDeal\Helper\Configuration
     */
    protected $configuration;

    /**
     * @var \MageSuite\DailyDeal\Service\OfferManagerInterface
     */
    protected $offerManager;

    /**
     * @var \Magento\Framework\Serialize\SerializerInterface
     */
    protected $serializer;

    public function __construct(
        \MageSuite\DailyDeal\Helper\Configuration $configuration,
        \MageSuite\DailyDeal\Service\OfferManagerInterface $offerManager,
        \Magento\Framework\Serialize\SerializerInterface $serializer
    ) {
        $this->configuration = $configuration;
        $this->offerManager = $offerManager;
        $this->serializer = $serializer;
    }

    public function aroundRepresentProduct(\Magento\Quote\Model\Quote\Item $subject, callable $proceed, $product)
    {
        if (!$this->configuration->isActive()) {
            return $proceed($product);
        }

        if ($subject->getProductId() != $product->getId()) {
            return $proceed($product);
        }

        if (!$this->offerManager->getOfferPrice($product)) {
            return $proceed($product);
        }

        $customOption = \MageSuite\DailyDeal\Service\OfferManager::ITEM_OPTION_DD_OFFER;

        $offerItemOption = $product->getCustomOption($customOption);

        if ($offerItemOption) {
            $itemOption = $subject->getOptionByCode($customOption);

            if (!$itemOption) {
                return $proceed($product);
            }

            return $itemOption->getValue() == $offerItemOption->getValue() ? true : false;
        }

        $product->addCustomOption(
            $customOption,
            true
        );

        return $proceed($product);
    }
}
