<?php
namespace SabaiApps\Framework\Model\EntityCollection;

class ParentEntitiesCountEntityCollectionDecorator extends AbstractEntityCollectionDecorator
{
    protected $_entityName, $_parentEntitiesCount;

    public function __construct($entityName, AbstractEntityCollection $collection)
    {
        parent::__construct($collection);
        $this->_entityName = $entityName;
    }

    #[\ReturnTypeWillChange]
    public function rewind()
    {
        $this->_collection->rewind();
        if (!isset($this->_parentEntitiesCount)) {
            $this->_parentEntitiesCount = [];
            if ($this->_collection->count() > 0) {
                $this->_parentEntitiesCount = $this->_model->getRepository($this->_entityName)->countParentsByIds($this->_collection->getAllIds());
                $this->_collection->rewind();
            }
        }
    }

    #[\ReturnTypeWillChange]
    public function current()
    {
        $current = $this->_collection->current();
        $id = $current->id;
        $current->setParentsCount(isset($this->_parentEntitiesCount[$id]) ? $this->_parentEntitiesCount[$id] : 0);

        return $current;
    }
}