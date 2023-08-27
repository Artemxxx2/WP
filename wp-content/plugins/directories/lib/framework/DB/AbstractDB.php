<?php
namespace SabaiApps\Framework\DB;

use SabaiApps\Framework\Exception;

abstract class AbstractDB
{
    /**
     * @var AbstractConnection
     */
    protected $_connection;
    /**
     * @var string
     */
    protected $_resourcePrefix;

    /**
     * Constructor
     *
     * @param AbstractConnection $connection
     * @param string $resourcePrefix
     */
    public function __construct(AbstractConnection $connection, $resourcePrefix = '')
    {
        $this->_connection = $connection->connect();
        $this->_resourcePrefix = $resourcePrefix;
    }

    /**
     * Gets the database connection object
     *
     * @return AbstractConnection
     */
    public function getConnection()
    {
        return $this->_connection;
    }

    /**
     * Gets the name of prefix used in datasource
     *
     * @return string
     */
    public function getResourcePrefix()
    {
        return $this->_resourcePrefix;
    }

    /**
     * Begins a transaction
     */
    public function begin()
    {
        $this->exec('BEGIN');
    }

    /**
     * Commits a transaction
     *
     */
    public function commit()
    {
        $this->exec('COMMIT');
    }

    /**
     * Performs a rollback of transaction
     *
     */
    public function rollback()
    {
        $this->exec('ROLLBACK');
    }

    /**
     * Queries the database
     *
     * @param string $sql
     * @param int $limit
     * @param int $offset
     * @return AbstractRowset
     * @throws Exception
     */
    public function query($sql, $limit = 0, $offset = 0)
    {
        $query = $this->getQuery($sql, $limit, $offset);
        if (!$rs = $this->_doQuery($query)) {
            throw new Exception(sprintf('%s SQL: %s', $this->lastError(), $query));
        }
        return $rs;
    }

    /**
     * Executes an SQL
     *
     * @param string $sql
     * @return int The number of rows affected.
     * @throws Exception
     */
    public function exec($sql)
    {
        if (!$this->_doExec($sql)) {
            throw new Exception(sprintf('%s SQL: %s', $this->lastError(), $sql));
        }
        return $this->affectedRows();
    }
    
    public function seedRandom($seed){}

    abstract public function getQuery($sql, $limit = 0, $offset = 0);
    abstract protected function _doQuery($sql);
    abstract protected function _doExec($sql);
    abstract public function affectedRows();
    abstract public function lastInsertId($name = null);
    abstract public function lastError();
    abstract public function escapeBool($value);
    abstract public function escapeString($value);
    abstract public function escapeBlob($value);
    abstract public function unescapeBlob($value);
    abstract public function getRandomFunc($seed = null);
    abstract public function getGroupConcatFunc($column, $separator = ',', $distinct = true);
}