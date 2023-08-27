<?php
namespace SabaiApps\Framework\Criteria;

class NotInCriteria extends InCriteria
{
    /**
     * Accepts a Visitor object
     *
     * @param IVisitor $visitor
     * @param mixed $valuePassed
     */
    public function acceptVisitor(IVisitor $visitor, &$valuePassed)
    {
        return $visitor->visitCriteriaNotIn($this, $valuePassed);
    }
}
