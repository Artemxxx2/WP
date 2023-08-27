<?php
namespace SabaiApps\Framework\DB;

class MySQLi extends MySQL
{
    protected function _doQuery($query)
    {
        if (!$rs = mysqli_query($this->_connection->getLink(), $query)) {
            return false;
        }

        return new MySQLiRowset($rs);
    }

    protected function _doExec($sql)
    {
        return mysqli_query($this->_connection->getLink(), $sql);
    }

    public function affectedRows()
    {
        return mysqli_affected_rows($this->_connection->getLink());
    }

    public function lastInsertId($name = null)
    {
        return mysqli_insert_id($this->_connection->getLink());
    }

    public function lastError()
    {
        return sprintf('%s(%s)', mysqli_error($this->_connection->getLink()), mysqli_errno($this->_connection->getLink()));
    }

    /**
     * Escapes a string value for MySQL DB
     *
     * @param string $value
     * @return string
     */
    public function escapeString($value)
    {
        return "'" . mysqli_real_escape_string($this->_connection->getLink(), $value) . "'";
    }
}