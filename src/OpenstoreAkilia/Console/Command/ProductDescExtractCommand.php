<?php

namespace OpenstoreAkilia\Console\Command;

use OpenstoreAkilia\Utils\ProductDescExtractor;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;
use League\Csv\Writer;

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
             ->addArgument(
                'output-file',
                InputArgument::REQUIRED,
                'Output file where to store the extracted data'
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
        $extracted = $extractor->extract();

        $output->writeln("Extracted data");
        $table = new Table($output);
        $table
            ->setHeaders(array_keys($extracted['data'][0]))
            ->setRows($extracted['data'])
        ;
        $table->render();

        $table = new Table($output);
        $output->writeln("Extracted stats");
        $table
            ->setHeaders(array_keys($extracted['stats'][0]))
            ->setRows($extracted['stats'])
        ;
        $table->render();


        $file = $input->getArgument('output-file');

        $this->createFile($file, $extracted['data']);

        $output->writeln("Server successfully extracted product desc attributes");
        return 0;
    }

    /**
     * @throws \RuntimeException
     * @param string $file file to write or overwrite
     * @param array $data
     */
    protected function createFile($file, array $data) {
        $csv = Writer::createFromPath($file, 'w');
        $csv->setDelimiter("\t");
        $csv->setEnclosure('"');
        // we insert the CSV header
        $headers = array_keys($data[0]);
        $csv->insertOne($headers);
        $csv->insertAll($data);

    }

}
