<?php
namespace SabaiApps\Framework\Model\EntityCollection;

class ChildEntitiesCountEntityCollectionDecorator extends AbstractEntityCollectionDecorator
{
    protected $_entityName, $_childEntitiesCount;

    public function __construct($entityName, AbstractEntityCollection $collection)
    {
        parent::__construct($collection);
        $this->_entityName = $entityName;
    }

    #[\ReturnTypeWillChange]
    public function rewind()
    {
        $this->_collection->rewind();
        if (!isset($this->_childEntitiesCount)) {
            $this->_childEntitiesCount = [];
            if ($this->_collection->count() > 0) {
                $parent_ids = $this->_collection->getAllIds();
                $this->_childEntitiesCount = $this->_model->getRepository($this->_entityName)->countByParent($parent_ids);
                $this->_collection->rewind();
            }
        }
    }

    #[\ReturnTypeWillChange]
    public function current()
    {
        $current = $this->_collection->current();
        $id = $current->id;
        $current->setChildrenCount(isset($this->_childEntitiesCount[$id]) ? $this->_childEntitiesCount[$id] : 0);

        return $current;
    }
}