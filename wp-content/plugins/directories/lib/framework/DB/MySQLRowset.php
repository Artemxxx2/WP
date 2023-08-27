<?php
namespace SabaiApps\Framework\DB;

class MySQLRowset extends AbstractRowset
{
    protected $_rs;

    public function __construct($rs)
    {
        $this->_rs = $rs;
    }

    public function fetchColumn($index = 0)
    {
        return ($row = mysql_fetch_row($this->_rs)) ? $row[$index] : false;
    }

    public function fetchRow()
    {
        return mysql_fetch_row($this->_rs);
    }

    public function fetchAssoc()
    {
        return mysql_fetch_assoc($this->_rs);
    }

    public function seek($offset = 0)
    {
        // suppress the E_WARNING error which mysql_data_seek() produces upon failure
        return @mysql_data_seek($this->_rs, $offset);
    }

    public function rowCount()
    {
        return mysql_num_rows($this->_rs);
    }
}