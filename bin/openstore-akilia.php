<?php

use OpenstoreAkilia\Config\OpenstoreAkiliaSetup;

// Bootstrap
try {
    // Step 1: init autoloader
    
    $autoloadFiles = array(__DIR__ . '/../vendor/autoload.php',
                           __DIR__ . '/../../../autoload.php');
    
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

    $directories = array(getcwd(), getcwd() . DIRECTORY_SEPARATOR . 'config');

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
    
    var_dump($setup->getConfig());
    die();
    
} catch (\Exception $e) {
    echo $e->getMessage() . "\n";
    exit(1);
}

$cli = new Symfony\Component\Console\Application('openstore-schema-core console', '1.0.0');
$cli->setCatchExceptions(true);

// commands
$cli->addCommands(array(    
    new OpenstoreAkilia\Console\AkiliaSyncDbCommand(),
));

// helpers
$helpers = array(
    'openstore-akilia-setup' => new OpenstoreAkilia\Console\Helper\ConfigurationHelper($setup),
    'question' => new Symfony\Component\Console\Helper\QuestionHelper(),
);
foreach ($helpers as $name => $helper) {
    $cli->getHelperSet()->set($helper, $name);
}

$cli->run();

