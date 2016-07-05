<?php

namespace OpenstoreAkilia\Console;

class CommandRepository
{

    /**
     * @var array
     */
    protected $commands;

    public function __construct()
    {
        $this->commands = [
            'openstore:akilia:syncdb' => new Command\AkiliaSyncDbCommand(),
            'openstore:product_desc:extract' => new Command\ProductDescExtractCommand()
        ];
    }

    /**
     * @param $name
     * @return \Symfony\Component\Console\Command\Command
     */
    public function getRegisteredCommand($name)
    {
        return $this->commands[$name];
    }

    /**
     * Return all registered commands
     * @return array
     */
    public function getRegisteredCommands()
    {
        return $this->commands;
    }
}
