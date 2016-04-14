<?php


namespace OpenstoreAkilia\Console;

use Symfony\Component\Console\Command\Command;


abstract class AbstractCommand extends Command
{



    /**
     * Return underlying database connection
     *
     * @return \OpenstoreAkilia\Config\OpenstoreAkiliaSetup
     */
    protected function getOpenstoreAkiliaSetup()
    {
        return $this->getHelper('openstore-akilia-setup')->getSetup();
    }
}
