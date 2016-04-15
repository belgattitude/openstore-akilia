<?php

class OpenstoreAkiliaTests {

    /**
     * Return base dir of project
     * @return string
     */
    public static function getBaseDir() {
        
        return dirname(__DIR__);
    }
    
    /**
     * Return console setup file
     * @return string
     */
    public static function getConfigFile() {
        
        $baseDir = self::getBaseDir();
        $cfg_file = $_SERVER['CONFIG_FILE'];
        $cfg_entities = $_SERVER['STANDARD_ENTITY_CONFIG_FILE'];
        
        if (preg_match('/^\./', $cfg_file)) {
            $cfg_file = realpath($baseDir . DIRECTORY_SEPARATOR . $cfg_file);
        }

        if (!$cfg_file) {
            throw new \Exception("Error in phpunit.xml[.dist] config, the CONFIG_FILE: $cfg_file cannot be located");
        }
        
        return $cfg_file;
    }
    
    /**
     * Return console entity sync setup file
     * @return string
     */
    public static function getEntityConfigFile() {
        
        $baseDir = self::getBaseDir();
        $cfg_file = $_SERVER['STANDARD_ENTITY_CONFIG_FILE'];
        
        if (preg_match('/^\./', $cfg_file)) {
            $cfg_file = realpath($baseDir . DIRECTORY_SEPARATOR . $cfg_file);
        }

        if (!$cfg_file) {
            throw new \Exception("Error in phpunit.xml[.dist] config, the STANDARD_ENTITY_CONFIG_FILE: $cfg_file cannot be located");
        }
        
        return $cfg_file;
    }
    
    

    
    
}
