<?php
namespace SabaiApps\Framework\Criteria;

abstract class AbstractArrayCriteria extends AbstractCriteria
{
    private $_field, $_array;

    public function __construct($field, array $array)
    {
        $this->_field = $field;
        $this->_array = $array;
    }

    public function getField()
    {
        return $this->_field;
    }

    public function getArray()
    {
        return $this->_array;
    }
}
