<?php
namespace SabaiApps\Directories\Component\Entity\Model;

use SabaiApps\Framework\Model\EntityCollection\AbstractEntityCollectionDecorator;
use SabaiApps\Framework\Model\EntityCollection\AbstractEntityCollection;

abstract class AbstractWithFieldEntityCollectionDecorator extends AbstractEntityCollectionDecorator
{
    protected $_fields, $_fieldIdVar, $_fieldObjectVarName;

    public function __construct(AbstractEntityCollection $collection, $fieldIdVar = 'field_id', $fieldObjectVarName = 'Field')
    {
        parent::__construct($collection);
        $this->_fieldIdVar = $fieldIdVar;
        $this->_fieldObjectVarName = $fieldObjectVarName;
    }

    #[\ReturnTypeWillChange]
    public function rewind()
    {
        $this->_collection->rewind();
        if (!isset($this->_fields)) {
            $this->_fields = [];
            if ($this->_collection->count() > 0) {
                $field_ids = [];
                while ($this->_collection->valid()) {
                    if ($field_id = $this->_collection->current()->{$this->_fieldIdVar}) {
                        $field_ids[$field_id] = $field_id;
                    }
                    $this->_collection->next();
                }
                if (!empty($field_ids)) {
                    foreach ($this->_model->getComponentEntities('Entity', 'Field', $field_ids) as $field) {
                        $this->_fields[$field->id] = $field;
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
        if (($field_id = $current->{$this->_fieldIdVar})
            && isset($this->_fields[$field_id])
        ) {
            $current->assignObject($this->_fieldObjectVarName, $this->_fields[$field_id]);
        } else {
            $current->assignObject($this->_fieldObjectVarName);
        }

        return $current;
    }
}