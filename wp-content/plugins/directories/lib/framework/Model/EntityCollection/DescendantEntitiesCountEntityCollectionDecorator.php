<?php
namespace SabaiApps\Framework\Model\EntityCollection;

class DescendantEntitiesCountEntityCollectionDecorator extends AbstractEntityCollectionDecorator
{
    protected $_entityName, $_descendantEntitiesCount;

    public function __construct($entityName, AbstractEntityCollection $collection)
    {
        parent::__construct($collection);
        $this->_entityName = $entityName;
    }

    #[\ReturnTypeWillChange]
    public function rewind()
    {
        $this->_collection->rewind();
        if (!isset($this->_descendantEntitiesCount)) {
            $this->_descendantEntitiesCount = [];
            if ($this->_collection->count() > 0) {
                $parent_ids = $this->_collection->getAllIds();
                $this->_descendantEntitiesCount = $this->_model->getRepository($this->_entityName)->countDescendantsByIds($parent_ids);
                $this->_collection->rewind();
            }
        }
    }

    #[\ReturnTypeWillChange]
    public function current()
    {
        $current = $this->_collection->current();
        $id = $current->id;
        $current->setDescendantsCount(isset($this->_descendantEntitiesCount[$id]) ? $this->_descendantEntitiesCount[$id] : 0);

        return $current;
    }
}