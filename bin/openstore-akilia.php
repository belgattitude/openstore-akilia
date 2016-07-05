<?php

use OpenstoreAkilia\Config\OpenstoreAkiliaSetup;
use OpenstoreAkilia\Console\CommandRepository;

// Bootstrap
try {
    // Step 1: init autoloader

    $autoloadFiles = [__DIR__ . '/../vendor/autoload.php',
                           __DIR__ . '/../../../autoload.php'];

    $found = false;
    foreach ($autoloadFiles as $autoloadFile) {
        if (file_exists($autoloadFile)) {
            $found = true;
            require_once $autoloadFile;
            break;
        }
    }
    if (!$found) {
        throw new \Exception('Cannot find composer vendor autoload, run composer update');
    }

    // Step 2 : init configuration

    $directories = [
        getcwd() . DIRECTORY_SEPARATOR . 'config',
        DIRNAME(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'config'
    ];

    $configFound = false;
    $configFile = null;
    foreach ($directories as $directory) {
        $configFile = $directory . DIRECTORY_SEPARATOR . 'openstore-akilia.config.php';
        if (file_exists($configFile)) {
            $configFound = true;
            break;
        }
    }
    if (!$found) {
        throw new \Exception("Cannot find configuration file '$configFile'");
    }

    $ds = DIRECTORY_SEPARATOR;
    $defaultEntitiesFile = __DIR__ . "{$ds}..{$ds}config{$ds}openstore-akilia-standard-sync-entities.config.php";

    $setup = OpenstoreAkiliaSetup::loadFromFiles([$defaultEntitiesFile, $configFile]);
} catch (\Exception $e) {
    echo $e->getMessage() . "\n";
    exit(1);
}

$cli = new Symfony\Component\Console\Application('openstore-schema-core console', '1.0.0');
$cli->setCatchExceptions(true);

// commands
$commandRepository = new CommandRepository();
$cli->addCommands($commandRepository->getRegisteredCommands());

// helpers
$helpers = [
    'openstore-akilia-setup' => new OpenstoreAkilia\Console\Helper\ConfigurationHelper($setup),
    'question' => new Symfony\Component\Console\Helper\QuestionHelper(),
];
foreach ($helpers as $name => $helper) {
    $cli->getHelperSet()->set($helper, $name);
}

$cli->run();
