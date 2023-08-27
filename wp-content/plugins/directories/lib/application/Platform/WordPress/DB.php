<?php
namespace SabaiApps\Directories\Platform\WordPress;

use SabaiApps\Framework\DB\MySQL;
use SabaiApps\Framework\DB\MySQLRowset;
use SabaiApps\Framework\DB\AbstractConnection;

class DB extends MySQL
{
    protected $_affectedRows;
    
    public function __construct(\wpdb $wpdb)
    {
        parent::__construct(new DBConnection($wpdb), $wpdb->prefix . 'drts_');
    }
    
    protected function _doQuery($query)
    {
        $wpdb = $this->_connection->getWpdb();
        $wpdb->hide_errors(); // query errors are handled by exceptions, so do not print them out
        $result = $wpdb->query($query);
        $wpdb->show_errors();

        return false === $result ? false : new DBRowset($wpdb->last_result);
    }

    protected function _doExec($sql)
    {
        $wpdb = $this->_connection->getWpdb();
        $wpdb->hide_errors(); // query errors are handled by exceptions, so do not print them out
        $result = $wpdb->query($sql);
        $wpdb->show_errors();
        if (false === $result) {
            $this->_affectedRows = -1;
            return false;
        }      
        
        $this->_affectedRows = $result;
        return true;
    }

    public function affectedRows()
    {
        return $this->_affectedRows;
    }

    public function lastInsertId($name = null)
    {
        return $this->_connection->getWpdb()->insert_id;
    }

    public function lastError()
    {
        return $this->_connection->getWpdb()->last_error;
    }

    public function escapeString($value)
    {
        return "'" . $this->_connection->getWpdb()->_real_escape($value) . "'";
    }
}

class DBRowset extends MySQLRowset
{
    protected $_rowIndex = 0;
    
    public function fetchColumn($index = 0)
    {
        if (!isset($this->_rs[$this->_rowIndex])) return false;
        
        $keys = array_keys((array)$this->_rs[$this->_rowIndex]);
        $key = $keys[$index];
        return $this->_rs[$this->_rowIndex]->$key;
    }

    public function fetchRow()
    {
        return array_values((array)$this->_rs[$this->_rowIndex]);
    }

    public function fetchAssoc()
    {
        return (array)$this->_rs[$this->_rowIndex];
    }

    public function seek($offset = 0)
    {
        $this->_rowIndex = $offset;
        return isset($this->_rs[$this->_rowIndex]);
    }

    public function rowCount()
    {
        return count($this->_rs);
    }
}

class DBConnection extends AbstractConnection
{
    protected $_wpdb;
    
    public function __construct(\wpdb $wpdb)
    {
        parent::__construct($wpdb->use_mysqli ? 'MySQLi' : 'MySQL');
        $this->_wpdb = $wpdb;
    }
    
    public function getWpdb()
    {
        return $this->_wpdb;
    }

    protected function _doConnect(){}
}