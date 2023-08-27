<?php
namespace SabaiApps\Framework\DB;

class RowsetIterator implements \Iterator, \Countable
{
    protected $_rs, $_key, $_count;

    public function __construct(AbstractRowset $rs)
    {
        $this->_rs = $rs;
        $this->_key = 0;
    }

    #[\ReturnTypeWillChange]
    public function rewind()
    {
        $this->_key = 0;
    }

    #[\ReturnTypeWillChange]
    public function valid()
    {
        return $this->_rs->seek($this->_key);
    }

    #[\ReturnTypeWillChange]
    public function next()
    {
        ++$this->_key;
    }

    #[\ReturnTypeWillChange]
    public function current()
    {
        return $this->_rs->fetchAssoc();
    }

    #[\ReturnTypeWillChange]
    public function key()
    {
        return $this->_key;
    }

    #[\ReturnTypeWillChange]
    public function count()
    {
        if (!isset($this->_count)) {
            $this->_count = $this->_rs->rowCount();
        }
        return $this->_count;
    }
    
    public function row()
    {
        return $this->_rs->fetchRow();
    }
}