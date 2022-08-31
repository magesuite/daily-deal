<?php

namespace MageSuite\DailyDeal\Observer;

class AddOfferToCart implements \Magento\Framework\Event\ObserverInterface
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
    private $serializer;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    private $messageManager;

    /**
     * @var \Magento\Framework\Controller\ResultFactory
     */
    protected $resultFactory;

    /**
     * @var \Magento\Checkout\Model\Cart
     */
    protected $cart;

    public function __construct(
        \MageSuite\DailyDeal\Helper\Configuration $configuration,
        \MageSuite\DailyDeal\Service\OfferManagerInterface $offerManager,
        \Magento\Framework\Serialize\SerializerInterface $serializer,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Framework\Controller\ResultFactory $resultFactory,
        \Magento\Checkout\Model\Cart $cart
    )
    {
        $this->configuration = $configuration;
        $this->offerManager = $offerManager;
        $this->serializer = $serializer;
        $this->messageManager = $messageManager;
        $this->resultFactory = $resultFactory;
        $this->cart = $cart;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if(!$this->configuration->isActive()){
            return $this;
        }

        $item = $observer->getEvent()->getData('quote_item');
        $item = $item->getParentItem() ? $item->getParentItem() : $item;

        $product = $item->getProduct();

        $offerPrice = $this->offerManager->getOfferPrice($product);

        if(!$offerPrice){
            return $this;
        }

        $finalPrice = $product->getFinalPrice();
        $offerPrice = min($finalPrice, $offerPrice);

        if(!$this->configuration->isQtyLimitationEnabled()){
            $this->updateProductPrice($item, $offerPrice);

            return $this;
        }

        $qty = $item->getQty();
        $offerLimit = $this->offerManager->getOfferLimit($product);

        if($product->getTypeId() != 'simple'){

            // For configurable items we need to check amount of products currently in the cart
            $qtyAmountInCart = $this->offerManager->getProductQtyInCart($product, $item->getQuoteId());

            // We need to decrease quantity by actual product qty
            $itemQtyInCart = $qty - $item->getQtyToAdd();
            $qtyAmountInCart -= $itemQtyInCart;

            $offerLimit = max(0, $offerLimit - $qtyAmountInCart);
        }

        if(!$offerLimit){
            $this->messageManager->addNoticeMessage(__('Requested amount of %1 isn\'t available.', $product->getName()));
            exit();
        }

        if($qty <= $offerLimit){
            $this->updateProductPrice($item, $offerPrice);

            return $this;
        }

        $item->setQty($offerLimit);
        $this->updateProductPrice($item, $offerPrice);

        $qtyLeft = $qty - $offerLimit;

        $this->addRegularItem(
            $observer->getEvent()->getData('product'),
            $qtyLeft
        );

        $this->messageManager->addNoticeMessage(__(
            'Requested amount of %1 in special price isn\'t available. %2 item(s) have been added with regular price.',
            $product->getName(),
            $qtyLeft
        ));

        return $this;
    }

    private function updateProductPrice($item, $offerPrice)
    {
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

    public function addRegularItem($product, $qty)
    {
        $request = new \Magento\Framework\DataObject(['qty' => $qty]);

        $regularItemProduct = clone $product;
        $regularItemProduct->addCustomOption(
            \MageSuite\DailyDeal\Service\OfferManager::ITEM_OPTION_DD_OFFER,
            false
        );

        try {
            $this->cart->getQuote()->addProduct($regularItemProduct, $request);
        } catch (\Magento\Framework\Exception\LocalizedException $e) {}
    }
}
