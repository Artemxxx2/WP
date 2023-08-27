<?php
namespace SabaiApps\Framework\Model\EntityCollection;

class ForeignEntityEntityCollectionDecorator extends AbstractEntityCollectionDecorator
{
    protected $_foreignKeyVar, $_foreignEntityName, $_foreignEntities, $_foreitnEntityObjectVarName;

    public function __construct($foreignKeyVar, $foreignEntityName, AbstractEntityCollection $collection, $foreignEntityObjectVarName = null)
    {
        parent::__construct($collection);
        $this->_foreignKeyVar = $foreignKeyVar;
        $this->_foreignEntityName = $foreignEntityName;
        $this->_foreitnEntityObjectVarName = isset($foreignEntityObjectVarName) ? $foreignEntityObjectVarName : $foreignEntityName;
    }

    #[\ReturnTypeWillChange]
    public function rewind()
    {
        if (!isset($this->_foreignEntities)) {
            $this->_foreignEntities = [];
            if ($this->_collection->count() > 0) {
                // Fetch all foreign entity IDs and call array_filter to filter out empty values
                $foreign_ids = array_filter($this->_collection->getArray($this->_foreignKeyVar, $this->_foreignKeyVar));
                if (!empty($foreign_ids)) {
                    $this->_foreignEntities = $this->_model->getRepository($this->_foreignEntityName)
                        ->fetchByIds($foreign_ids)
                        ->getArray();
                }
            }
        }
        $this->_collection->rewind();
    }

    #[\ReturnTypeWillChange]
    public function current()
    {
        $current = $this->_collection->current();
        $foreign_id = $current->{$this->_foreignKeyVar};
        if (isset($this->_foreignEntities[$foreign_id])) {
            $current->assignObject($this->_foreitnEntityObjectVarName, $this->_foreignEntities[$foreign_id]);
        } else {
            $current->assignObject($this->_foreitnEntityObjectVarName);
        }

        return $current;
    }
}