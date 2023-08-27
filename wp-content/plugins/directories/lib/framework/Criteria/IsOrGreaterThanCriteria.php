<?php
namespace SabaiApps\Framework\Criteria;

class IsOrGreaterThanCriteria extends AbstractValueCriteria
{
    /**
     * Accepts a Visitor object
     *
     * @param IVisitor $visitor
     * @param mixed $valuePassed
     */
    public function acceptVisitor(IVisitor $visitor, &$valuePassed)
    {
        return $visitor->visitCriteriaIsOrGreaterThan($this, $valuePassed);
    }
}
