<?php
namespace SabaiApps\Directories\Component\Entity\Model;

use SabaiApps\Framework\Model\EntityCollection\AbstractEntityCollectionDecorator;
use SabaiApps\Framework\Model\EntityCollection\AbstractEntityCollection;

abstract class AbstractWithEntityEntityCollectionDecorator extends AbstractEntityCollectionDecorator
{
    protected $_entities, $_entityIdVar, $_entityObjectVarName, $_entityBundleNameVar;

    public function __construct(AbstractEntityCollection $collection, $entityIdVar = 'entity_id', $entityObjectVarName = 'Entity', $entityBundleNameVar = 'bundle_name')
    {
        parent::__construct($collection);
        $this->_entityIdVar = $entityIdVar;
        $this->_entityObjectVarName = $entityObjectVarName;
        $this->_entityBundleNameVar = $entityBundleNameVar;
    }

    #[\ReturnTypeWillChange]
    public function rewind()
    {
        $this->_collection->rewind();
        if (!isset($this->_entities)) {
            $this->_entities = [];
            if ($this->_collection->count() > 0) {
                $entity_ids = [];
                while ($this->_collection->valid()) {
                    $item = $this->_collection->current();
                    if (($entity_id = $item->{$this->_entityIdVar})
                        && ($bundle_name = $item->{$this->_entityBundleNameVar})
                    ) {
                        $entity_ids[$bundle_name][$entity_id] = $entity_id;
                    }
                    $this->_collection->next();
                }
                if (!empty($entity_ids)) {
                    foreach (array_keys($entity_ids) as $bundle_name) {
                        if (!$bundle = $this->_model->Entity_Bundle($bundle_name)) continue;

                        foreach ($this->_model->Entity_Entities($bundle->entitytype_name, $entity_ids[$bundle_name], false) as $entity) {
                            $this->_entities[$entity->getId()] = $entity;
                        }
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
        if (($entity_id = $current->{$this->_entityIdVar})
            && isset($this->_entities[$entity_id])
        ) {
            $current->assignObject($this->_entityObjectVarName, $this->_entities[$entity_id]);
        } else {
            $current->assignObject($this->_entityObjectVarName);
        }

        return $current;
    }
}