<?php

namespace OpenstoreAkilia\Db;

use Zend\Db\Adapter\Adapter as ZendDb;

class DbExecuter
{

    /**
     * @var Adapter
     */
    protected $adapter;

    /**
     *
     * @var boolean
     */
    protected $log_to_stdout = true;

    /**
     *
     * @var string
     */
    protected $last_entity = null;

    /**
     * 
     * @param ZendDb $adapter
     * @param boolean $log_to_stdout
     */
    public function __construct(ZendDb $adapter, $log_to_stdout=true)
    {
        $this->adapter = $adapter;
        $this->log_to_stdout = $log_to_stdout;
    }


    /**
     * Execute a query on the database and logs it
     *
     * @throws Exception
     *
     * @param string $key
     *            name of the query
     * @param string $query
     * @param boolean $disable_foreign_key_checks
     * @param string $entity
     * @return void
     */
    public function executeSQL($key, $query, $disable_foreign_key_checks = true, $entity=null)
    {
        if ($entity !== null && $entity != $this->last_entity) {
            $this->log("------------------------------------------------------");
            $this->log("Entity: '$entity'");
            $this->log("------------------------------------------------------");
            $this->last_entity = $entity;
        }

        $this->log(" * Sync::executeSQL '$key'...");

        $total_time_start = microtime(true);

        if ($disable_foreign_key_checks) {
            $this->adapter->query('set foreign_key_checks=0');
            $this->log("  * FK : Foreign key check disabled");
        }

        try {
            $time_start = microtime(true);
            $result = $this->adapter->query($query, ZendDb::QUERY_MODE_EXECUTE);
            $affected_rows = $result->getAffectedRows();

            // Log stuffs
            $time_stop = microtime(true);
            $time = number_format(($time_stop - $time_start), 2);
            $formatted_query = preg_replace('/(\n)|(\r)|(\t)/', ' ', $query);
            $formatted_query = preg_replace('/(\ )+/', ' ', $formatted_query);
            $this->log("  * SQL: " . substr(trim($formatted_query), 0, 70) . '...') ;
            $this->log("  * SQL: Query time $time sec(s))");
        } catch (\Exception $e) {
            $err = $e->getMessage();
            $msg = "Error running query ({$err}) : \n--------------------\n$query\n------------------\n";
            $this->log("[+] $msg\n");
            if ($disable_foreign_key_checks) {
                $this->log("[Error] Error restoring foreign key checks");
                $this->adapter->query('set foreign_key_checks=1');
            }
            throw new \Exception($msg);
        }

        if ($disable_foreign_key_checks) {
            $time_start = microtime(true);
            $this->adapter->query('set foreign_key_checks=1');
            $time_stop = microtime(true);
            $time = number_format(($time_stop - $time_start), 2);
            $this->log("  * FK : Foreign keys restored");
        }
        $time_stop = microtime(true);
        $time = number_format(($time_stop - $total_time_start), 2);
        $this->log(" * Time: $time secs, affected rows $affected_rows.");
    }

    /**
     * Log message
     *
     * @param string $message
     * @param int $priority
     * @return void
     */
    protected function log($message, $priority = null)
    {
        if ($this->log_to_stdout) {
            echo "$message\n";
        }
    }


    /**
     * @param integer $size
     * @return string
     */
    protected function convertMemorySize($size)
    {
        $unit = [
            'b',
            'kb',
            'mb',
            'gb',
            'tb',
            'pb'
        ];
        return @round($size / pow(1024, ($i = floor(log($size, 1024)))), 2) . ' ' . $unit[$i];
    }
}
