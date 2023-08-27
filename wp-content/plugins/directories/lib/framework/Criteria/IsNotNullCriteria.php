<?php
namespace SabaiApps\Framework\Criteria;

class IsNotNullCriteria extends AbstractCriteria
{
    private $_field;

    public function __construct($field)
    {
        $this->_field = $field;
    }

    public function getField()
    {
        return $this->_field;
    }

    /**
     * Accepts a Visitor object
     *
     * @param IVisitor $visitor
     * @param mixed $valuePassed
     */
    public function acceptVisitor(IVisitor $visitor, &$valuePassed)
    {
        return $visitor->visitCriteriaIsNotNull($this, $valuePassed);
    }
}
