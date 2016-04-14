<?php

namespace OpenstoreAkilia\Console;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
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
        $synchronizer->synchronize();
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
        ->setHelp(<<<EOT
Process synchronization from akilia tables to openstore-schema-core tables.

<comment>Hint:</comment> Please have a look at the configuration file config/openstore-akilia.config.php
    
EOT
        );
    }
}
