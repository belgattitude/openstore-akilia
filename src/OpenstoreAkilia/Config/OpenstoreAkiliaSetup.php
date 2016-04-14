<?php

namespace OpenstoreAkilia\Config;

use Zend\Db\Adapter\Adapter as ZendDb;

class OpenstoreAkiliaSetup
{

    /**
     * openstore-akilia configuration
     * @var 
     */
    protected $config;

    /**
     *
     * @var ZendDb
     */
    protected $zendDb;

    /**
     * Constructor
     * 
     * @param array $config openstore-akilia configuration
     */
    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * Return underlying configuration
     * @return array
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * Create configuration from a regular php file
     * 
     * @param array $files valid listing of files
     * @return OpenstoreAkiliaSetup
     */
    public static function loadFromFiles(array $files)
    {
        $checked_files = [];
        $configs = [];
        $config = [];
        foreach ($files as $idx => $file) {
            if (!file_exists($file) || !is_readable($file)) {
                throw new \Exception(__METHOD__ . " Cannot locate configuration file: $file");
            }
            $configs[$idx] = include $file;
            if (!$configs[$idx]) {
                throw new \Exception("Cannot parse or empty configuration file '$file' ([" . implode(',', $files) . "])");
            }
            if (!array_key_exists('openstore-akilia', $configs[$idx])) {
                throw new \Exception("Config file lacks a required 'openstore-akilia' top level key in file '$file'.");
            }

            $config = array_merge($config, $configs[$idx]['openstore-akilia']);
        }
        return new OpenstoreAkiliaSetup($config);
    }

    /**
     * 
     * @throws \Exception
     * @param string $key optional key
     * @return string|array
     */
    public function getSynchronizerConfig($key = null)
    {
        if ($key === null) {
            return $this->config['synchronizer'];
        } elseif (!array_key_exists($key, $this->config['synchronizer'])) {
            throw new \Exception("Cannot key synchronizer config '$key', it does not exists");
        } else {
            return $this->config['synchronizer'][$key];
        }
    }

    /**
     * Set the database adapter
     * @param ZendDb $zendDb
     */
    public function setDatabaseAdapter(ZendDb $zendDb)
    {
        $this->zendDb = $zendDb;
    }

    /**
     * Return database adapter
     * @return ZendDb
     */
    public function getDatabaseAdapter()
    {
        if ($this->zendDb === null) {
            $this->loadDatabaseAdapterFromConfig();
        }
        return $this->zendDb;
    }

    /**
     * 
     */
    protected function loadDatabaseAdapterFromConfig()
    {
        if (!array_key_exists('adapter', $this->config)) {
            throw new \Exception("Missing 'adapter' configuration section in your config.");
        }
        $this->zendDb = new ZendDb($this->config['adapter']);
    }
}
