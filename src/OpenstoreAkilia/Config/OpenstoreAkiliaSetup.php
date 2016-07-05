<?php

namespace OpenstoreAkilia\Config;

use Zend\Db\Adapter\Adapter as ZendDb;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\ServiceManager\ServiceManager;


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
     * Return logger
     * @return \Psr\Log\LoggerInterface
     */
    public function getLogger()
    {
        //@todo
        return new \Psr\Log\NullLogger();
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

            $config = array_merge_recursive($config, $configs[$idx]['openstore-akilia']);
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
     * 
     * @param array $entity_names
     * @return array
     */
    public function getMappedSyncEntities(array $entity_names=null)
    {
        $entities = [];
        $entities_map = $this->getSynchronizerConfig('entities_map');

        if ($entity_names === null) {
            // take from config
            $entity_names = $this->getSynchronizerConfig('entities');
        }
        foreach ($entity_names as $entity_name) {
            $entity_name = strtolower($entity_name);
            if (array_key_exists($entity_name, $entities_map)) {
                $entities[$entity_name] = $entities_map[$entity_name];
            } else {
                throw new \Exception("Entity name '$entity_name' has no associated mapping to a class, config file only describe: " . implode(',', array_keys($entities_map)));
            }
        }
        return $entities;
    }

    /**
     * 
     * @return string
     * @throws \Exception
     */
    public function getAkilia1Path()
    {
        if (!array_key_exists('akilia1', $this->config)) {
            throw new \Exception("Missing 'akilia1' configuration section in your config.");
        }
        if (!array_key_exists('path', $this->config['akilia1'])) {
            throw new \Exception("Missing 'akilia1.path' configuration section in your config.");
        }

        $path = $this->config['akilia1']['path'];
        if (!is_dir($path)) {
            throw new \Exception("'akilia1_path' (config: $path) does not exists.");
        }

        return $path;
    }

    /**
     * 
     * @return string
     */
    public function getOpenbridgePath()
    {
        $akilia1_path = $this->getAkilia1Path();
        $ds = DIRECTORY_SEPARATOR;
        $openbridge_path = "{$akilia1_path}{$ds}openbridge";
        if (!is_dir($openbridge_path)) {
            throw new \Exception("Cannot locate openbridge path '$openbridge_path' does not exists.");
        }

        return $openbridge_path;
    }

    /**
     * 
     * @param string $module_name
     * @return string
     */
    public function getOpenbridgeModulePath($module_name)
    {
        $openbridge_path = $this->getOpenbridgePath();
        $ds = DIRECTORY_SEPARATOR;
        $module_path = "{$openbridge_path}{$ds}modules{$ds}$module_name";
        if (!is_dir($module_path)) {
            throw new \Exception("Cannot locate openbridge module '$module_name' path '$module_path' does not exists.");
        }

        return $module_path;
    }


    /**
     * 
     * @return ServiceLocatorInterface
     */
    public function getServiceLocator()
    {
        $sm = new ServiceManager();
        $sm->setFactory('Zend\Db\Adapter\Adapter', function () {
            return $this->getDatabaseAdapter();
        });

        return $sm;
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
