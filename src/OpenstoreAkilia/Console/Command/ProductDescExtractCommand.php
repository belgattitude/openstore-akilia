<?php

namespace OpenstoreAkilia\Console\Command;

use OpenstoreAkilia\Utils\ProductDescExtractor;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


class ProductDescExtractCommand extends AbstractCommand
{
    
    /**
     * @var StandaloneServer
     */
    protected $server;


    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('openstore:product_desc:extract')
             ->setDescription(
                 'Extract attributes from product description'
               )
             ->setHelp(<<<EOT
Parse and extract attributes/values from product description
EOT
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $logger = $this->getLogger($output);

        $openstoreSetup = $this->getOpenstoreAkiliaSetup();
        $extractor = new ProductDescExtractor($openstoreSetup->getDatabaseAdapter());
        $extractor->extract();

        $output->writeln("Server successfully extracted product desc attributes");
        return 0;
    }
}
