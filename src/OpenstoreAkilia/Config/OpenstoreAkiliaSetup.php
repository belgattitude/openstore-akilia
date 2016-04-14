<?php

namespace OpenstoreAkilia\Config;

class OpenstoreAkiliaSetup
{
    /**
     * openstore-akilia configuration
     * @var 
     */
    protected $config;


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
     * Create configuration from a regular php file
     * 
     * @param array $files valid listing of files
     * @return OpenstoreAkiliaSetup
     */
    public static function loadFromFiles(array $files)
    {
        $checked_files = [];
        foreach ($files as $file) {
            if (!file_exists($file) || !is_readable($file)) {
                throw new \Exception(__METHOD__ . " Cannot locate configuration file: $file");
            }
            $checked_files[] = $file;
        }
        $config = array_merge($files);

        if (!$config) {
            throw new \Exception("Cannot parse or empty configuration file(s): [" . implode(',', $files) . "]");
        }
        return new OpenstoreAkiliaSetup($config);
    }
}
