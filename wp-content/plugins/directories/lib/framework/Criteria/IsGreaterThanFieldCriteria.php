<?php
namespace SabaiApps\Framework\Criteria;

class IsGreaterThanFieldCriteria extends AbstractFieldCriteria
{
    /**
     * Accepts a Visitor object
     *
     * @param IVisitor $visitor
     * @param mixed $valuePassed
     */
    public function acceptVisitor(IVisitor $visitor, &$valuePassed)
    {
        return $visitor->visitCriteriaIsGreaterThanField($this, $valuePassed);
    }
}
