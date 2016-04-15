<?php

namespace OpenstoreAkiliaTest\Config;

use OpenstoreAkiliaTests;
use OpenstoreAkilia\Config\OpenstoreAkiliaSetup;
use Zend\Db\Adapter\Adapter;


class OpenstoreAkiliaSetupTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var OpenstoreAkiliaSetup
     */
    protected $setup;


    protected function setUp()
    {
        $cfg = include OpenstoreAkiliaTests::getConfigFile();
        $this->setup = new \OpenstoreAkilia\Config\OpenstoreAkiliaSetup($cfg['openstore-akilia']);
    }

    protected function getConfigFiles()
    {
        return [OpenstoreAkiliaTests::getEntityConfigFile(), OpenstoreAkiliaTests::getConfigFile()];
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown()
    {
    }

    /**
     * @covers OpenstoreAkilia\Config\OpenstoreAkiliaSetup::getConfig
     */
    public function testGetConfig()
    {
        $this->assertInternalType('array', $this->setup->getConfig());
    }

    /**
     * @covers OpenstoreAkilia\Config\OpenstoreAkiliaSetup::loadFromFiles
     */
    public function testLoadFromFiles()
    {
        $loaded = OpenstoreAkiliaSetup::loadFromFiles([OpenstoreAkiliaTests::getConfigFile()]);
        $this->assertEquals($this->setup->getConfig(), $loaded->getConfig());

        $loaded2 = OpenstoreAkiliaSetup::loadFromFiles($this->getConfigFiles());
        $cfg2 = $loaded2->getConfig();
        $this->assertArrayHasKey('standard_entities', $cfg2['synchronizer']);


        $standard_entities_config = $loaded2->getSynchronizerConfig('standard_entities');
        $this->assertInternalType('array', $standard_entities_config);
    }

    /**
     * @covers OpenstoreAkilia\Config\OpenstoreAkiliaSetup::getSynchronizerConfig
     */
    public function testGetSynchronizerConfig()
    {
        $cfg = $this->setup->getSynchronizerConfig();
        $this->assertEquals($this->setup->getConfig()['synchronizer'], $cfg);
    }

    /**
     * @covers OpenstoreAkilia\Config\OpenstoreAkiliaSetup::setDatabaseAdapter
     */
    public function testSetDatabaseAdapter()
    {
        $db_params = $this->setup->getConfig()['adapter'];
        $adapter = new Adapter($db_params);
        $this->setup->setDatabaseAdapter($adapter);
        $this->assertEquals($adapter, $this->setup->getDatabaseAdapter());
    }

    /**
     * @covers OpenstoreAkilia\Config\OpenstoreAkiliaSetup::getDatabaseAdapter
     */
    public function testGetDatabaseAdapter()
    {
        $adapter = $this->setup->getDatabaseAdapter();
        $this->assertInstanceOf('Zend\Db\Adapter\Adapter', $adapter);
    }
}
