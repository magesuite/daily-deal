<?php

namespace MageSuite\DailyDeal\Observer;

class AddOfferToCart implements \Magento\Framework\Event\ObserverInterface
{
    protected \MageSuite\DailyDeal\Service\DailyDealApplier $dailyDealApplier;
    protected \Psr\Log\LoggerInterface $logger;
    protected \Magento\Framework\Message\ManagerInterface $messageManager;

    public function __construct(
        \MageSuite\DailyDeal\Service\DailyDealApplier $dailyDealApplier,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Message\ManagerInterface $messageManager
    ) {
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

        $item = $item->getParentItem() ? $item->getParentItem() : $item;

        try {
            $this->dailyDealApplier->apply($item);
        } catch (\Magento\Framework\Exception\ValidatorException $e) {
            $this->messageManager->addNoticeMessage($e->getMessage());
        } catch (\Magento\Framework\Exception\NotFoundException $e) {
            $this->logger->critical($e->getMessage());
        }

        return $this;
    }
}
