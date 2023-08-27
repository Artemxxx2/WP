<?php
namespace SabaiApps\Framework\Model\EntityCollection;

use SabaiApps\Framework\Criteria\InCriteria;

class AssocEntitiesEntityCollectionDecorator extends AbstractEntityCollectionDecorator
{
    protected $_linkEntityName, $_linkSelfKey, $_assocEntityTable, $_assocEntityName, $_assocEntities;

    public function __construct($linkEntityName, $linkSelfKey, $assocEntityTable, $assocEntityName, AbstractEntityCollection $collection)
    {
        parent::__construct($collection);
        $this->_linkEntityName = $linkEntityName;
        $this->_linkSelfKey = $linkSelfKey;
        $this->_assocEntityTable = $assocEntityTable;
        $this->_assocEntityName = $assocEntityName;
    }

    #[\ReturnTypeWillChange]
    public function rewind()
    {
        $this->_collection->rewind();
        if (!isset($this->_assocEntities)) {
            $this->_assocEntities = [];
            if ($this->_collection->count() > 0) {
                $criteria = new InCriteria($this->_linkSelfKey, $this->_collection->getAllIds());
                $fields = [$this->_linkSelfKey, $this->_assocEntityTable . '.*'];
                if ($rs = $this->_model->getGateway($this->_linkEntityName)->selectByCriteria($criteria, $fields)) {
                    foreach ($rs as $row) {
                        $entity = $this->_model->create($this->_assocEntityName);
                        $entity->initVars($row);
                        $this->_assocEntities[$row[$this->_linkSelfKey]][] = $entity;
                    }
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
        $entities = !empty($this->_assocEntities[$id]) ? $this->_assocEntities[$id] : [];
        $current->assignObject($this->_assocEntityName, $this->_model->createCollection($this->_assocEntityName, $entities));

        return $current;
    }
}