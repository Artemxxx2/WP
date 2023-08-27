<?php
namespace SabaiApps\Framework\Criteria;

class IsOrGreaterThanFieldCriteria extends AbstractFieldCriteria
{
    /**
     * Accepts a Visitor object
     *
     * @param IVisitor $visitor
     * @param mixed $valuePassed
     */
    public function acceptVisitor(IVisitor $visitor, &$valuePassed)
    {
        return $visitor->visitCriteriaIsOrGreaterThanField($this, $valuePassed);
    }
}
