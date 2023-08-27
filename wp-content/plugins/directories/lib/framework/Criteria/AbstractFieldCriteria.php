<?php
namespace SabaiApps\Framework\Criteria;

abstract class AbstractFieldCriteria extends AbstractCriteria
{
    private $_field, $_field2;

    public function __construct($field, $field2)
    {
        $this->_field = $field;
        $this->_field2 = $field2;
    }

    public function getField()
    {
        return $this->_field;
    }

    public function getField2()
    {
        return $this->_field2;
    }
}
