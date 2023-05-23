<?php
namespace MageSuite\DailyDeal\Console\Command;

class DailyDealRefresh extends \Symfony\Component\Console\Command\Command
{
    /**
     * @var \Magento\Framework\App\State
     */
    protected $state;

    /**
     * @var \Magento\Framework\Config\ScopeInterface $scope
     */
    protected $scope;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \MageSuite\DailyDeal\Service\OfferManagerInterfaceFactory
     */
    protected $offerManagerFactory;

    public function __construct(
        \Magento\Framework\App\State $state,
        \Magento\Framework\Config\ScopeInterface $scope,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \MageSuite\DailyDeal\Service\OfferManagerInterfaceFactory $offerManagerFactory
    ) {
        parent::__construct();

        $this->state = $state;
        $this->scope = $scope;
        $this->storeManager = $storeManager;
        $this->offerManagerFactory = $offerManagerFactory;
    }

    protected function configure()
    {
        $this->setName('dailydeal:refresh');

        $this->addArgument(
            'store_id',
            \Symfony\Component\Console\Input\InputArgument::OPTIONAL,
            'Store Id'
        );
    }

    protected function execute(
        \Symfony\Component\Console\Input\InputInterface $input,
        \Symfony\Component\Console\Output\OutputInterface $output
    ) {
        if ($this->scope->getCurrentScope() !== 'frontend') {
            $this->state->setAreaCode('frontend');
        }

        $specificStoreId = $input->getArgument('store_id');

        $stores = $specificStoreId !== null
            ? [$specificStoreId => $specificStoreId]
            : $this->storeManager->getStores(true);

        $storeIds = array_keys($stores);
        sort($storeIds);

        $offerManager = $this->offerManagerFactory->create();

        foreach ($storeIds as $storeId) {
            $offerManager->refreshOffers($storeId);
        }

        return \Magento\Framework\Console\Cli::RETURN_SUCCESS;
    }
}
