<?php
namespace SabaiApps\Framework\DB;

class PDOMySQLRowset extends AbstractRowset
{
    protected $_stmt, $_offset;

    public function __construct(\PDOStatement $stmt)
    {
        $this->_stmt = $stmt;
    }

    public function fetchColumn($index = 0)
    {
        return $this->_stmt->fetchColumn($index);
    }

    public function fetchRow()
    {
        return $this->_fetch(\PDO::FETCH_NUM);
    }

    public function fetchAssoc()
    {
        return $this->_fetch(\PDO::FETCH_ASSOC);
    }

    protected function _fetch($style)
    {
        if (isset($this->_offset)) {
            $result = $this->_stmt->fetch($style, \PDO::FETCH_ORI_ABS, $this->_offset);
            $this->_offset = null;
            return $result;
        }
        return $this->_stmt->fetch($style);
    }

    public function seek($offset = 0)
    {
        $this->_offset = $offset;
        return true;
    }

    public function rowCount()
    {
        return $this->_stmt->rowCount();
    }
}