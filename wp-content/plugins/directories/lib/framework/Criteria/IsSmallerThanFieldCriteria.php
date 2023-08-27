<?php
namespace SabaiApps\Framework\Criteria;

class IsSmallerThanFieldCriteria extends AbstractFieldCriteria
{
    /**
     * Accepts a Visitor object
     *
     * @param IVisitor $visitor
     * @param mixed $valuePassed
     */
    public function acceptVisitor(IVisitor $visitor, &$valuePassed)
    {
        return $visitor->visitCriteriaIsSmallerThanField($this, $valuePassed);
    }
}
