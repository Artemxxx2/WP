<?php
namespace SabaiApps\Framework\Criteria;

class EmptyCriteria extends AbstractCriteria
{
    public function isEmpty()
    {
        return true;
    }

    /**
     * Accepts a Visitor object
     *
     * @param IVisitor $visitor
     * @param mixed $valuePassed
     */
    public function acceptVisitor(IVisitor $visitor, &$valuePassed)
    {
        return $visitor->visitCriteriaEmpty($this, $valuePassed);
    }
}