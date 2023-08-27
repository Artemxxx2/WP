<?php
namespace SabaiApps\Framework\Model\EntityCollection;

use SabaiApps\Framework\Model\Model;
use SabaiApps\Framework\Model\AbstractEntity;

abstract class AssocEntityCollection extends AbstractEntityCollection
{
    protected $_assoc, $_emptyEntity, $_count;

    public function __construct($name, array $assoc, AbstractEntity $emptyEntity, Model $model)
    {
        parent::__construct($model, $name);
        $this->_assoc = $assoc;
        $this->_emptyEntity = $emptyEntity;
    }

    #[\ReturnTypeWillChange]
    public function count()
    {
        return count($this->_assoc);
    }

    #[\ReturnTypeWillChange]
    public function valid()
    {
        return isset($this->_assoc[$this->_key]);
    }

    public function getCurrent($index)
    {
        $entity = clone $this->_emptyEntity;
        $this->_loadRow($entity, $this->_assoc[$index]);

        return $entity;
    }

    abstract protected function _loadRow(AbstractEntity $entity, array $row);
}