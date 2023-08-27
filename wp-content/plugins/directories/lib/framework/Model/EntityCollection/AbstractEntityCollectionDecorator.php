<?php
namespace SabaiApps\Framework\Model\EntityCollection;

abstract class AbstractEntityCollectionDecorator extends AbstractEntityCollection
{
    /**
     * @var AbstractEntityCollection
     */
    protected $_collection;

    /**
     * Constructor
     *
     * @param AbstractEntityCollection $collection
     */
    public function __construct(AbstractEntityCollection $collection)
    {
        parent::__construct($collection->getModel(), $collection->getName());
        $this->_collection = $collection;
    }

    #[\ReturnTypeWillChange]
    public function count()
    {
        return $this->_collection->count();
    }

    #[\ReturnTypeWillChange]
    public function rewind()
    {
        $this->_collection->rewind();
    }

    #[\ReturnTypeWillChange]
    public function valid()
    {
        return $this->_collection->valid();
    }

    #[\ReturnTypeWillChange]
    public function next()
    {
        $this->_collection->next();
    }

    #[\ReturnTypeWillChange]
    public function current()
    {
        return $this->_collection->current();
    }

    #[\ReturnTypeWillChange]
    public function key()
    {
        return $this->_collection->key();
    }
    
    public function getCurrent($index)
    {
        return $this->_collection->getCurrent($index);
    }
}