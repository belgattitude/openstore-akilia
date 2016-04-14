<?php

namespace OpenstoreAkilia\Sync\Entities;

use OpenstoreAkilia\Config\OpenstoreAkiliaSetup;
use OpenstoreAkilia\Db\DbExecuter;

abstract class AbstractEntity
{


    /**
     *
     * @var OpenstoreAkiliaSetup
     */
    protected $setup;


    /**
     *
     * @var DbExecuter 
     */
    protected $dbExecuter;

    /**
     *
     * @var string timestamp Y-m-d H:i:s to maintain sync
     */
    protected $legacy_synchro_at;

    /**
     *
     * @var string
     */
    protected $openstoreDb;

    /**
     *
     * @var string
     */
    protected $akilia2Db;

    /**
     *
     * @var string
     */
    protected $intelaccessDb;

    /**
     *
     * @var string
     */
    protected $akilia1Db;


    /**
     *
     * @var string
     */
    protected $default_language;

    /**
     *
     * @var string
     */
    protected $default_language_sfx;


    /**
     * @param DbExecuter $db
     * @param OpenstoreAkiliaSetup
     */
    public function __construct(DbExecuter $db, OpenstoreAkiliaSetup $setup)
    {
        $this->setup = $setup;
        $this->dbExecuter = $db;
        $this->loadConfiguration();
    }

    /**
     * Set the legacy_sycnhro_at parameter
     * @param string $legacy_synchro_at timestamp Y-m-d H:i:s
     */
    public function setLegacySynchroAt($legacy_synchro_at)
    {
        $this->legacy_synchro_at = $legacy_synchro_at;
    }

    /**
     * @return string
     */
    public function getLegacySynchroAt()
    {
        return $this->legacy_synchro_at;
    }

    protected function loadConfiguration()
    {
        $this->legacy_synchro_at = date('Y-m-d H:i:s');
        $this->akilia2Db = $this->setup->getSynchronizerConfig('db_akilia2');
        $this->openstoreDb = $this->setup->getDatabaseAdapter()->getCurrentSchema();
        $this->akilia1Db = $this->setup->getSynchronizerConfig('db_akilia1');
        $this->intelaccessDb = $this->setup->getSynchronizerConfig('db_intelaccess');
        $this->akilia1lang = $this->setup->getSynchronizerConfig('akilia1_language_map');
        $this->default_language = $this->setup->getSynchronizerConfig('default_language');
        $this->default_language_sfx = $this->akilia1lang[$this->default_language];
    }

    abstract public function synchronize();
}
