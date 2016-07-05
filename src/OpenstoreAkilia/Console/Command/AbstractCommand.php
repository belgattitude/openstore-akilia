<?php


namespace OpenstoreAkilia\Console\Command;

use Symfony\Component\Console\Command\Command;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Logger\ConsoleLogger;


abstract class AbstractCommand extends Command
{

    /**
     * @var LoggerInterface
     */
    protected $logger;


    use OpenstoreAkiliaSetupTrait;


    /**
     * Get or create a logger
     *
     * @return LoggerInterface
     */
    public function getLogger(OutputInterface $output) {
        if ($this->logger === null) {
            $this->logger = new ConsoleLogger($output);
        }
        return $this->logger;
    }


}
