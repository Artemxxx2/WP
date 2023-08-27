<?php
namespace SabaiApps\Framework\DB;

class PDOMySQL extends AbstractDB
{
    protected $_affectedRows;

    /**
     * Gets a SQL select statement
     *
     * @param string $sql
     * @param int $limit
     * @param int $offset
     * @return string
     */
    public function getQuery($sql, $limit = 0, $offset = 0)
    {
        if (intval($limit) > 0) $sql .=  sprintf(' LIMIT %d, %d', $offset, $limit);

        return $sql;
    }

    /**
     * Queries the database
     *
     * @param string $query
     * @return mixed PDOMySQLRowset on success, false on error
     */
    protected function _doQuery($query)
    {
        if (!$stmt = $this->_connection->getPDO()->query($query)) {
            return false;
        }

        return new PDOMySQLRowset($stmt);
    }

    /**
     * Executes an SQL query against the DB
     *
     * @param string $sql
     * @return bool
     */
    protected function _doExec($sql)
    {
        $result = $this->_connection->getPDO()->exec($sql);

        if (false === $result) {
            $this->_affectedRows = -1;
            return false;
        }

        $this->_affectedRows = $result;
        return true;
    }

    /**
     * Gets the primary key of te last inserted row
     *
     * @param string $name
     * @return mixed Integer or false on error.
     */
    public function lastInsertId($name = null)
    {
        return $this->_connection->getPDO()->lastInsertId($name);
    }

    /**
     * Gets the number of affected rows
     *
     * @return int
     */
    public function affectedRows()
    {
        return $this->_affectedRows;
    }

    /**
     * Gets the last error occurred
     *
     * @return string
     */
    public function lastError()
    {
        return sprintf('%s(%s)', $this->_connection->getPDO()->errorInfo()[2], $this->_connection->getPDO()->errorCode());
    }

    /**
     * Escapes a boolean value for MySQL DB
     *
     * @param bool $value
     * @return int
     */
    public function escapeBool($value)
    {
        return $this->_connection->getPDO()->quote($value, \PDO::PARAM_BOOL);
    }

    /**
     * Escapes a string value for MySQL DB
     *
     * @param string $value
     * @return string
     */
    public function escapeString($value)
    {
        return $this->_connection->getPDO()->quote($value, \PDO::PARAM_STR);
    }

    /**
     * Escapes a blob value for MySQL DB
     *
     * @param string $value
     * @return string
     */
    public function escapeBlob($value)
    {
        return $this->_connection->getPDO()->quote($value, \PDO::PARAM_LOB);
    }

    /**
     * Unescapes a blob value retrieved from MySQL DB
     *
     * @param string $value
     * @return string
     */
    public function unescapeBlob($value)
    {
        return $value;
    }

    public function getRandomFunc($seed = null)
    {
        return isset($seed) ? 'RAND(' . (int)$seed . ')' : 'RAND()';
    }

    public function getGroupConcatFunc($column, $separator = ',', $distinct = true)
    {
        if ($distinct) $column = 'DISTINCT ' . $column;
        return 'GROUP_CONCAT(' . $column . ' SEPARATOR \'' . $separator . '\')';
    }
}