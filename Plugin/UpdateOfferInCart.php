<?php

namespace MageSuite\DailyDeal\Plugin;

class UpdateOfferInCart
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
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;

    public function __construct(
        \MageSuite\DailyDeal\Helper\Configuration $configuration,
        \MageSuite\DailyDeal\Service\OfferManagerInterface $offerManager,
        \Magento\Framework\Message\ManagerInterface $messageManager
    ) {
        $this->configuration = $configuration;
        $this->offerManager = $offerManager;
        $this->messageManager = $messageManager;
    }

    public function beforeUpdateItems(\Magento\Checkout\Model\Cart $subject, $data)
    {
        if (!$this->configuration->isActive() || !$this->configuration->isQtyLimitationEnabled()) {
            return [$data];
        }

        $quote = $subject->getQuote();

        foreach ($data as $itemId => $value) {

            $item = $quote->getItemById($itemId);

            if (!$item) {
                continue;
            }

            if ($item->getParentItem()) {
                continue;
            }

            $option = $item->getOptionByCode(
                \MageSuite\DailyDeal\Service\OfferManager::ITEM_OPTION_DD_OFFER
            );

            if (!$option || !$option->getValue()) {
                continue;
            }

            $qty = isset($data[$itemId]['qty']) ? (double)$data[$itemId]['qty'] : false;
            $oldQty = $item->getQty();

            if (!$qty || $qty <= $oldQty) {
                continue;
            }

            $product = $item->getProduct();

            $offerLimit = $this->offerManager->getOfferLimit($product);

            if ($product->getTypeId() != 'simple') {
                $qtyAmountInCart = $this->offerManager->getProductQtyInCart($product, $item->getQuoteId());

                // We need to decrease quantity by actual product qty
                $qtyAmountInCart = $qtyAmountInCart - $oldQty;

                $offerLimit = max(0, $offerLimit - $qtyAmountInCart);
            }

            if ($qty <= $offerLimit) {
                continue;
            }

            $data[$itemId]['qty'] = $offerLimit;
            $data[$itemId]['before_suggest_qty'] = $offerLimit;

            $this->messageManager->addNoticeMessage(__('Requested amount of %1 isn\'t available.', $product->getName()));
        }

        return [$data];
    }
}
