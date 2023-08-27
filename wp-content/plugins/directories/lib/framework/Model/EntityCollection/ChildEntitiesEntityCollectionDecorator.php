<?php
namespace SabaiApps\Framework\Model\EntityCollection;

use SabaiApps\Framework\Criteria\InCriteria;

class ChildEntitiesEntityCollectionDecorator extends AbstractEntityCollectionDecorator
{
    protected $_parentKey, $_entityName, $_childEntities;

    public function __construct($entityName, $parentKey, AbstractEntityCollection $collection)
    {
        parent::__construct($collection);
        $this->_parentKey = $parentKey;
        $this->_entityName = $entityName;
    }

    #[\ReturnTypeWillChange]
    public function rewind()
    {
        $this->_collection->rewind();
        if (!isset($this->_childEntities)) {
            $this->_childEntities = [];
            if ($this->_collection->count() > 0) {
                $criteria = new InCriteria($this->_parentKey, $this->_collection->getAllIds());
                $children = $this->_model->getRepository($this->_entityName)->fetchByCriteria($criteria);
                foreach ($children as $child) {
                    $this->_childEntities[$child->parent][] = $child;
                }
                $this->_collection->rewind();
            }
        }
    }

    #[\ReturnTypeWillChange]
    public function current()
    {
        $current = $this->_collection->current();
        $id = $current->id;
        $entities = !empty($this->_childEntities[$id]) ? $this->_childEntities[$id] : [];
        $current->assignObject('Children', $this->getModel()->createCollection($this->_entityName, $entities));

        return $current;
    }
}