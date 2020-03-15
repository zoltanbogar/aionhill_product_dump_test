<?php

namespace AionHill\Dump\Console;

use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\CatalogInventory\Model\StockRegistry;
use Magento\Framework\Exception\LocalizedException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ProductQueryCommand
 * @package AionHill\Dump\Console
 */
class ProductQueryCommand extends Command
{
    /**
     * @var CollectionFactory
     */
    private $collectionFactory;
    /**
     * @var StockRegistry
     */
    private $stockRegistry;
    /**
     * @var
     */
    private $products;

    /**
     * ProductQueryCommand constructor.
     * @param CollectionFactory $collectionFactory
     * @param StockRegistry $stockRegistry
     */
    public function __construct(CollectionFactory $collectionFactory, StockRegistry $stockRegistry)
    {
        $this->collectionFactory = $collectionFactory;
        $this->stockRegistry = $stockRegistry;

        parent::__construct();
    }

    /**
     *
     */
    protected function configure()
    {
        $this->setName('dump:products')
            ->setDescription('Displays the list of products, their SKU\'s, statuses and stocks.');

        parent::configure();
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null
     */
    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        try {
            $table = new Table($output);
            $table->setHeaders(['SKU', 'Status', 'Quantity']);

            $this->getProducts();

            foreach ($this->products as $product) {
                $table->addRow([
                    $product['sku'],
                    $this->processStatus($product['status']),
                    $this->getProductQuantity($product['entity_id']),
                ]);
            }

            $table->render();

            return \Magento\Framework\Console\Cli::RETURN_SUCCESS;
        } catch (LocalizedException $e) {
            $output->writeln('An error occured while we tried to collect the products.');
            $output->writeln($e->getMessage());

            return \Magento\Framework\Console\Cli::RETURN_FAILURE;
        } catch (\Exception $e) {
            $output->writeln($e->getMessage());

            return \Magento\Framework\Console\Cli::RETURN_FAILURE;
        }
    }

    /**
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function getProducts() : void
    {
        $collection = $this->collectionFactory->create();
        $collection->joinAttribute(
            'status',
            'catalog_product/status',
            'entity_id',
            null,
            'inner'
        );

        $this->products = $collection->getItems();
    }

    /**
     * @param $status
     * @return string
     */
    private function processStatus($status) : string
    {
        return (int) $status === 1 ? 'Enabled' : 'Disabled';
    }

    /**
     * @param $product_id
     * @return int
     */
    private function getProductQuantity($product_id) : int
    {
        $product_stock = $this->stockRegistry->getStockItem($product_id);

        return (int) $product_stock->getQty();
    }
}
