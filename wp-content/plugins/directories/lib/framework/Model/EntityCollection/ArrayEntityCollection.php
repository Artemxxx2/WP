<?php
namespace SabaiApps\Framework\Model\EntityCollection;

use SabaiApps\Framework\Model\Model;

class ArrayEntityCollection extends AbstractEntityCollection
{
    private $_entities;

    public function __construct(Model $model, $name, array $entities = [])
    {
        parent::__construct($model, $name);
        $this->_entities = array_values($entities); // reindex array
    }

    #[\ReturnTypeWillChange]
    public function valid()
    {
        return array_key_exists($this->_key, $this->_entities);
    }

    public function getCurrent($index)
    {
        return $this->_entities[$index];
    }

    #[\ReturnTypeWillChange]
    public function count()
    {
        return count($this->_entities);
    }
}