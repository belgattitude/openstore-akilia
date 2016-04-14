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
     * @return void
     */
    public function executeSQL($key, $query, $disable_foreign_key_checks = true)
    {
        $this->log("Sync::executeSQL '$key'...\n");

        $total_time_start = microtime(true);

        if ($disable_foreign_key_checks) {
            $time_start = microtime(true);
            $this->adapter->query('set foreign_key_checks=0');
            $time_stop = microtime(true);
            $time = number_format(($time_stop - $time_start), 2);
            $this->log("  * Disabling foreign key checks (in time $time sec(s))\n");
        }

        try {
            $time_start = microtime(true);
            $result = $this->adapter->query($query, ZendDb::QUERY_MODE_EXECUTE);
            $affected_rows = $result->getAffectedRows();

            // Log stuffs
            $time_stop = microtime(true);
            $time = number_format(($time_stop - $time_start), 2);
            $this->log("  * Querying database (in time $time sec(s))\n");
            $formatted_query = preg_replace('/(\n)|(\r)|(\t)/', ' ', $query);
            $formatted_query = preg_replace('/(\ )+/', ' ', $formatted_query);
            $this->log("  * " . substr($formatted_query, 0, 60));
        } catch (\Exception $e) {
            $err = $e->getMessage();
            $msg = "Error running query ({$err}) : \n--------------------\n$query\n------------------\n";
            $this->log("[+] $msg\n");
            if ($disable_foreign_key_checks) {
                $this->log("[Error] Error restoring foreign key checks\n");
                $this->adapter->query('set foreign_key_checks=1');
            }
            throw new \Exception($msg);
        }

        if ($disable_foreign_key_checks) {
            $time_start = microtime(true);
            $this->adapter->query('set foreign_key_checks=1');
            $time_stop = microtime(true);
            $time = number_format(($time_stop - $time_start), 2);
            $this->log("  * RESTORING foreign key checks  (in time $time sec(s))\n");
        }
        $time_stop = microtime(true);
        $time = number_format(($time_stop - $total_time_start), 2);
        $this->log(" [->] Success in ExecuteSQL '$key' in total $time secs, affected rows $affected_rows.\n");
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
