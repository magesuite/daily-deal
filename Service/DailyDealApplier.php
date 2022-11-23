<?php

namespace MageSuite\DailyDeal\Service;

class DailyDealApplier
{
    protected \Magento\Checkout\Model\Cart $cart;
    protected \MageSuite\DailyDeal\Helper\Configuration $configuration;
    protected \MageSuite\DailyDeal\Service\OfferManagerInterface $offerManager;
    protected \Magento\Framework\Controller\ResultFactory $resultFactory;
    protected \Magento\Framework\Serialize\SerializerInterface $serializer;

    public function __construct(
        \Magento\Checkout\Model\Cart $cart,
        \MageSuite\DailyDeal\Helper\Configuration $configuration,
        \MageSuite\DailyDeal\Service\OfferManagerInterface $offerManager,
        \Magento\Framework\Controller\ResultFactory $resultFactory,
        \Magento\Framework\Serialize\SerializerInterface $serializer
    ) {
        $this->cart = $cart;
        $this->configuration = $configuration;
        $this->offerManager = $offerManager;
        $this->resultFactory = $resultFactory;
        $this->serializer = $serializer;
    }

    public function apply(
        \Magento\Quote\Api\Data\CartItemInterface $item
    ): bool {
        if (!$this->configuration->isActive()) {
            return false;
        }

        $isDailyDealCustomOption = $item->getProduct()->getCustomOption(\MageSuite\DailyDeal\Service\OfferManager::ITEM_OPTION_DD_OFFER);

        if ($isDailyDealCustomOption && $isDailyDealCustomOption->getValue() === false) {
            return false;
        }

        $product = $item->getProduct();

        $offerPrice = $this->offerManager->getOfferPrice($product);

        if (empty($offerPrice)) {
            return false;
        }

        $finalPrice = $product->getFinalPrice();
        $offerPrice = min($finalPrice, $offerPrice);
        $offerLimit = $this->offerManager->getOfferLimit($product);

        if (!$this->configuration->isQtyLimitationEnabled() || empty($offerLimit)) {
            $this->updateProductPrice($item, $offerPrice);

            return true;
        }

        $qty = $item->getQty();

        if ($product->getTypeId() != \Magento\Catalog\Model\Product\Type::TYPE_SIMPLE) {

            // For configurable items we need to check amount of products currently in the cart
            $qtyAmountInCart = $this->offerManager->getProductQtyInCart($product, $item->getQuoteId());

            // We need to decrease quantity by actual product qty
            $itemQtyInCart = $qty - $item->getQtyToAdd();
            $qtyAmountInCart -= $itemQtyInCart;

            $offerLimit = max(0, $offerLimit - $qtyAmountInCart);
        }

        if (!$offerLimit) {
            throw new \Magento\Framework\Exception\ValidatorException(
                __('Requested amount of %1 isn\'t available.', $product->getName())
            );
        }

        if ($qty <= $offerLimit) {
            $this->updateProductPrice($item, $offerPrice);

            return true;
        }

        $item->setQty($offerLimit);
        $this->updateProductPrice($item, $offerPrice);

        $qtyLeft = $qty - $offerLimit;

        $this->addRegularItem($product, $qtyLeft);

        throw new \Magento\Framework\Exception\ValidatorException(
            __(
                'Requested amount of %1 in special price isn\'t available. %2 item(s) have been added with regular price.',
                $product->getName(),
                $qtyLeft
            )
        );
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

    protected function addRegularItem(
        \Magento\Catalog\Api\Data\ProductInterface $product,
        int $qty
    ): void {
        $request = new \Magento\Framework\DataObject(['qty' => $qty]);

        $product->addCustomOption(
            \MageSuite\DailyDeal\Service\OfferManager::ITEM_OPTION_DD_OFFER,
            false
        );

        try {
            $this->cart->getQuote()->addProduct($product, $request);
        } catch (\Magento\Framework\Exception\LocalizedException $e) {} // phpcs:ignore
    }
}
