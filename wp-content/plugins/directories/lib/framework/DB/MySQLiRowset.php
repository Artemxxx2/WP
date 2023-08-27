<?php
namespace SabaiApps\Framework\DB;

class MySQLiRowset extends AbstractRowset
{
    public function fetchColumn($index = 0)
    {
        return ($row = mysqli_fetch_row($this->_rs)) ? $row[$index] : false;
    }

    public function fetchRow()
    {
        return mysqli_fetch_row($this->_rs);
    }

    public function fetchAssoc()
    {
        return mysqli_fetch_assoc($this->_rs);
    }

    public function seek($offset = 0)
    {
        // mysqli_data_seek() returns null on success, false otherwise according to php.net
        return false !== mysqli_data_seek($this->_rs, $offset);
    }

    public function rowCount()
    {
        return mysqli_num_rows($this->_rs);
    }
}