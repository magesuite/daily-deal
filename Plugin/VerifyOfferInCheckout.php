<?php

namespace MageSuite\DailyDeal\Plugin;

class VerifyOfferInCheckout
{
    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    protected $quoteRepository;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;

    /**
     * @var \MageSuite\DailyDeal\Helper\Configuration
     */
    protected $configuration;

    /**
     * @var \MageSuite\DailyDeal\Service\OfferManagerInterface
     */
    protected $offerManager;

    public function __construct(
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \MageSuite\DailyDeal\Helper\Configuration $configuration,
        \MageSuite\DailyDeal\Service\OfferManagerInterface $offerManager
    ) {
        $this->quoteRepository = $quoteRepository;
        $this->messageManager = $messageManager;
        $this->configuration = $configuration;
        $this->offerManager = $offerManager;
    }

    public function aroundPlaceOrder(
        \Magento\Quote\Api\CartManagementInterface $subject,
        $proceed,
        $cartId,
        $paymentMethod = null
    ) {
        if (!$this->configuration->isActive()) {
            return $proceed($cartId, $paymentMethod);
        }

        $quote = $this->quoteRepository->getActive($cartId);

        $validate = true;

        foreach ($quote->getAllItems() as $item) {
            if ($item->getParentItem()) {
                continue;
            }

            $option = $item->getOptionByCode(
                \MageSuite\DailyDeal\Service\OfferManager::ITEM_OPTION_DD_OFFER
            );

            if (!$option || !$option->getValue()) {
                continue;
            }

            $validate = $this->offerManager->validateOfferInQuote($item->getProduct(), $item->getQty());

            if (!$validate) {
                break;
            }
        }

        if (!$validate) {

            $this->offerManager->applyAction(
                $item->getProduct(),
                \MageSuite\DailyDeal\Service\OfferManager::TYPE_REMOVE
            );

            $this->messageManager->addError(__('Offer is ended and product was removed from the cart.'));
            return false;
        }

        return $proceed($cartId, $paymentMethod);
    }
}
