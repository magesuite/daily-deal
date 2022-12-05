<?php

namespace MageSuite\DailyDeal\Observer;

class AddOfferToCart implements \Magento\Framework\Event\ObserverInterface
{
    protected \Magento\Checkout\Model\Cart $cart;
    protected \MageSuite\DailyDeal\Service\DailyDealApplier $dailyDealApplier;
    protected \Psr\Log\LoggerInterface $logger;
    protected \Magento\Framework\Message\ManagerInterface $messageManager;

    public function __construct(
        \Magento\Checkout\Model\Cart $cart,
        \MageSuite\DailyDeal\Service\DailyDealApplier $dailyDealApplier,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->cart = $cart;
        $this->dailyDealApplier = $dailyDealApplier;
        $this->logger = $logger;
        $this->messageManager = $messageManager;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $items = $observer->getEvent()->getData('items');

        if (empty($items)) {
            return $this;
        }

        $item = array_shift($items);

        if (empty($item)) {
            return $this;
        }

        $product = $item->getProduct();

        try {
            if ($this->dailyDealApplier->apply($item)) {
                return $this;
            }
        } catch (\Magento\Framework\Exception\InvalidArgumentException $e) {
            $this->messageManager->addNoticeMessage($e->getMessage());
            exit;
        } catch (\Magento\Framework\Exception\ValidatorException $e) {
            return $this;
        }

        $isDailyDealCustomOption = $product->getCustomOption(\MageSuite\DailyDeal\Service\OfferManager::ITEM_OPTION_DD_OFFER);

        if ($isDailyDealCustomOption && $isDailyDealCustomOption->getValue() === false) {
            return false;
        }

        $regularItemProduct = clone $product;
        $this->dailyDealApplier->addRegularItem(
            $regularItemProduct,
            $this->cart->getQuote()
        );

        $this->messageManager->addNoticeMessage(__(
            'Requested amount of %1 in special price isn\'t available. %2 item(s) have been added with regular price.',
            $product->getName(),
            $this->dailyDealApplier->getQtyLeft()
        ));

        return $this;
    }
}
