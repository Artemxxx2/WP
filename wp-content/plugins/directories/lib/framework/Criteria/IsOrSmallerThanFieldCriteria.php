<?php
namespace SabaiApps\Framework\Criteria;

class IsOrSmallerThanFieldCriteria extends AbstractFieldCriteria
{
    /**
     * Accepts a Visitor object
     *
     * @param IVisitor $visitor
     * @param mixed $valuePassed
     */
    public function acceptVisitor(IVisitor $visitor, &$valuePassed)
    {
        return $visitor->visitCriteriaIsOrSmallerThanField($this, $valuePassed);
    }
}
