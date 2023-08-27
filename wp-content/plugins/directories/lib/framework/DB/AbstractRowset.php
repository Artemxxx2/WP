<?php
namespace SabaiApps\Framework\DB;

abstract class AbstractRowset implements \IteratorAggregate, \Countable
{
    /**
     * @return RowsetIterator
     */
    #[\ReturnTypeWillChange]
    public function getIterator()
    {
        return new RowsetIterator($this);
    }

    /**
     * Implementation of the Countable interface
     *
     * @return int
     */
    #[\ReturnTypeWillChange]
    public function count()
    {
        return $this->rowCount();
    }
    
    /**
     * @return string
     */
    public function fetchSingle()
    {
        return $this->fetchColumn(0);
    }

    /**
     * @param int $index
     * @return string
     */
    abstract public function fetchColumn($index = 0);
    /**
     * @return array
     */
    abstract public function fetchAssoc();
    /**
     * @return array
     */
    abstract public function fetchRow();
    /**
     * @param int $rowNum
     * @return bool
     */
    abstract public function seek($offset = 0);
    /**
     * @return int
     */
    abstract public function rowCount();
}