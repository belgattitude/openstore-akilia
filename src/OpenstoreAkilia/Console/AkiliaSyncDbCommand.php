<?php

namespace OpenstoreAkilia\Console;

class AkiliaSyncDbCommand extends AbstractCommand
{
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
