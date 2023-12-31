<?php
namespace SabaiApps\Framework\Paginator;

abstract class AbstractPaginator implements \Iterator, \Countable
{
    protected $_perpage, $_limit, $_key = 0, $_currentPage = 1, $_elementOffset = 0, $_elementLimit;
    private $_elementCount;

    public function __construct($perpage, $limit = 0)
    {
        $this->_perpage = intval($perpage) ? $perpage : 10;
        $this->_limit = $limit;
    }

    /**
     * Gets the number of items per page
     *
     * @return int
     */
    public function getPerPage()
    {
        return $this->_perpage;
    }

    public function setCurrentPage($page)
    {
        if ($page <= 1 // invalid page
            || $this->getElementCount() <= ($page - 1) * $this->_perpage // the page does not exist
        ) {
            $this->_currentPage = 1;
        } else {
            $this->_currentPage = $page;
        }
        $this->_elementLimit = null; // reset to recalculate

        return $this;
    }

    public function getCurrentPage()
    {
        return $this->_currentPage;
    }

    private function _calculateElementOffsetAndLimit()
    {
        if (isset($this->_elementLimit)) return $this->_elementLimit;

        if (!$count = $this->getElementCount()) {
            $this->_elementLimit = 0;
            return $this->_elementLimit;
        }

        $this->_elementOffset = ($this->_currentPage - 1) * $this->_perpage;
        if ($this->_elementOffset > $count) {
            $this->_elementOffset = 0;
        }
        $remaining = $count - $this->_elementOffset;
        $this->_elementLimit = $remaining < $this->_perpage ? $remaining : $this->_perpage;

        return $this->_elementLimit;
    }

    public function getElementOffset()
    {
        $this->_calculateElementOffsetAndLimit();

        return $this->_elementOffset;
    }

    public function getElementLimit()
    {
        $this->_calculateElementOffsetAndLimit();

        return $this->_elementLimit;
    }

    public function getElements()
    {
        if (0 === $this->_calculateElementOffsetAndLimit()) return $this->_getEmptyElements();

        return $this->_getElements($this->_elementLimit, $this->_elementOffset);
    }

    protected function _getEmptyElements()
    {
        return new \ArrayObject([]);
    }

    public function getElementCount()
    {
        if (!isset($this->_elementCount)) {
            $this->_elementCount = $this->_getElementCount();
            if ($this->_limit
                && $this->_elementCount > $this->_limit
            ) {
                $this->_elementCount = $this->_limit;
            }
        }
        return $this->_elementCount;
    }

    #[\ReturnTypeWillChange]
    public function count()
    {
        return ceil($this->getElementCount() / $this->_perpage);
    }

    #[\ReturnTypeWillChange]
    public function rewind()
    {
        $this->_key = 0;
    }

    #[\ReturnTypeWillChange]
    public function valid()
    {
        return $this->getElementCount() > $this->key() * $this->_perpage;
    }

    #[\ReturnTypeWillChange]
    public function next()
    {
        ++$this->_key;
    }

    #[\ReturnTypeWillChange]
    public function current()
    {
        return $this->key() + 1;
    }

    #[\ReturnTypeWillChange]
    public function key()
    {
        return $this->_key;
    }

    /**
     * @param int $limit
     * @param int $offset
     * @return \Traversable
     */
    protected abstract function _getElements($limit, $offset);

    /**
     * @return int
     */
    protected abstract function _getElementCount();
}