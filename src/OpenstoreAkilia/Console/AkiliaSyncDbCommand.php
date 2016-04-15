<?php

namespace OpenstoreAkilia\Console;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use OpenstoreAkilia\Sync\AkiliaSynchronizer;

class AkiliaSyncDbCommand extends AbstractCommand
{

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $setup = $this->getOpenstoreAkiliaSetup();

        $synchronizer = new AkiliaSynchronizer($setup);

        $entities = $input->getOption('entities');

        if (!$entities) {
            $output->writeln('<error>Error, if you want to sync all entitites use --entities=*</error>');
            throw new \Exception('Usage Exception');
        } else {
            $entities = explode(',', $entities);
            $entities = array_map('trim', $entities);
        }

        if (count($entities) == 1 && $entities[0] == '*') {
            $synchronizer->synchronizeAll();
        } else {
            $synchronizer->synchronize($entities);
        }
    }


    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('openstore:akilia:syncdb')
             ->setDescription(
                 'Synchronize openstore database with akilia db content.'
             )
        ->setDefinition([
            new InputOption(
                'entities',
                null,
                InputOption::VALUE_REQUIRED,
                "Entity(ies) to sync, (separated by comma's) or '*' for all"
            )
        ])

        ->setHelp(<<<EOT
Process synchronization from akilia tables to openstore-schema-core tables.

<comment>Hint:</comment> Please have a look at the configuration file config/openstore-akilia.config.php
    
EOT
        );
    }
}
