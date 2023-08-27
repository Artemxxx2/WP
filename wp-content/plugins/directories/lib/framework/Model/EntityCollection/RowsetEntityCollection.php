<?php
namespace SabaiApps\Framework\Model\EntityCollection;

use SabaiApps\Framework\Model\Model;
use SabaiApps\Framework\Model\AbstractEntity;
use SabaiApps\Framework\DB\AbstractRowset;

abstract class RowsetEntityCollection extends AbstractEntityCollection
{
    protected $_rs, $_emptyEntity, $_count;

    public function __construct($name, AbstractRowset $rs, AbstractEntity $emptyEntity, Model $model)
    {
        parent::__construct($model, $name);
        $this->_rs = $rs;
        $this->_emptyEntity = $emptyEntity;
    }

    #[\ReturnTypeWillChange]
    public function count()
    {
        if (!isset($this->_count)) {
            $this->_count = count($this->_rs);
        }

        return $this->_count;
    }

    #[\ReturnTypeWillChange]
    public function valid()
    {
        return $this->_key < $this->count();
    }

    public function getCurrent($index)
    {
        $this->_rs->seek($index);
        $entity = clone $this->_emptyEntity;
        $this->_loadRow($entity, $this->_rs->fetchAssoc());

        return $entity;
    }

    protected function _loadRow(AbstractEntity $entity, array $row)
    {
        $entity->initVars($row);
    }
}