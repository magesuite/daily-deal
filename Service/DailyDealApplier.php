<?php

namespace MageSuite\DailyDeal\Service;

class DailyDealApplier
{
    protected \MageSuite\DailyDeal\Helper\Configuration $configuration;
    protected \MageSuite\DailyDeal\Service\OfferManagerInterface $offerManager;
    protected \Magento\Framework\Controller\ResultFactory $resultFactory;
    protected \Magento\Framework\Serialize\SerializerInterface $serializer;

    protected int $qtyLeft = 0;

    public function __construct(
        \MageSuite\DailyDeal\Helper\Configuration $configuration,
        \MageSuite\DailyDeal\Service\OfferManagerInterface $offerManager,
        \Magento\Framework\Controller\ResultFactory $resultFactory,
        \Magento\Framework\Serialize\SerializerInterface $serializer
    ) {
        $this->configuration = $configuration;
        $this->offerManager = $offerManager;
        $this->resultFactory = $resultFactory;
        $this->serializer = $serializer;
    }

    public function apply(
        \Magento\Quote\Api\Data\CartItemInterface $item
    ): ?bool {

        if (!$this->configuration->isActive()) {
            throw new \Magento\Framework\Exception\ValidatorException(__('Daily Deal is disabled in store configuration.'));
        }

        $isDailyDealCustomOption = $item->getProduct()->getCustomOption(\MageSuite\DailyDeal\Service\OfferManager::ITEM_OPTION_DD_OFFER);

        if ($isDailyDealCustomOption && $isDailyDealCustomOption->getValue() === false) {
            return false;
        }

        $product = $item->getProduct();

        $offerPrice = $this->offerManager->getOfferPrice($product);

        if (empty($offerPrice)) {
            throw new \Magento\Framework\Exception\ValidatorException(__('Offer Price has not been found.'));
        }

        $finalPrice = $product->getFinalPrice();
        $offerPrice = min($finalPrice, $offerPrice);
        $offerLimit = $this->offerManager->getOfferLimit($product);

        if (!$this->configuration->isQtyLimitationEnabled() || empty($offerLimit)) {
            $this->updateProductPrice($item, $offerPrice);

            return true;
        }

        $qty = $item->getQty();

        if ($product->getTypeId() !== \Magento\Catalog\Model\Product\Type::TYPE_SIMPLE) {

            // For configurable items we need to check amount of products currently in the cart
            $qtyAmountInCart = $this->offerManager->getProductQtyInCart($product, $item->getQuoteId());

            // We need to decrease quantity by actual product qty
            $itemQtyInCart = $qty - $item->getQtyToAdd();
            $qtyAmountInCart -= $itemQtyInCart;

            $offerLimit = max(0, $offerLimit - $qtyAmountInCart);
        }

        if (!$offerLimit) {
            throw new \Magento\Framework\Exception\InvalidArgumentException(
                __('Requested amount of %1 isn\'t available.', $product->getName())
            );
        }

        if ($qty <= $offerLimit) {
            $this->updateProductPrice($item, $offerPrice);

            return true;
        }

        $item->setQty($offerLimit);
        $this->updateProductPrice($item, $offerPrice);

        $this->qtyLeft = $qty - $offerLimit;

        return false;
    }

    public function getQtyLeft(): int
    {
        return $this->qtyLeft;
    }

    protected function updateProductPrice(
        \Magento\Quote\Api\Data\CartItemInterface $item,
        float $offerPrice
    ): void {
        $infoBuyRequest = $item->getBuyRequest();

        $product = $item->getProduct();

        if ($infoBuyRequest) {
            $infoBuyRequest->addData([
                \MageSuite\DailyDeal\Service\OfferManager::ITEM_OPTION_DD_OFFER => true
            ]);

            $infoBuyRequest->setValue($this->serializer->serialize($infoBuyRequest->getData()));
            $infoBuyRequest->setCode('info_buyRequest');
            $infoBuyRequest->setProduct($product);

            $item->addOption($infoBuyRequest);
        }

        $item->addOption([
            'product_id' => $product->getId(),
            'code' => \MageSuite\DailyDeal\Service\OfferManager::ITEM_OPTION_DD_OFFER,
            'value' => $this->serializer->serialize(true)
        ]);

        $item->setCustomPrice($offerPrice);
        $item->setOriginalCustomPrice($offerPrice);
        $item->getProduct()->setIsSuperMode(true);
    }

    public function addRegularItem(
        \Magento\Catalog\Api\Data\ProductInterface $product,
        $quote,
        $qty = null
    ): void {

        $qty ??= $this->getQtyLeft();

        if (empty($qty)){
            return;
        }

        $request = new \Magento\Framework\DataObject(['qty' => $qty]);

        $product->addCustomOption(
            \MageSuite\DailyDeal\Service\OfferManager::ITEM_OPTION_DD_OFFER,
            false
        );

        try {
            $quote->addProduct($product, $request);
        } catch (\Magento\Framework\Exception\LocalizedException $e) {} // phpcs:ignore
    }
}
