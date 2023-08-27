<?php
namespace SabaiApps\Framework\Criteria;

class IsFieldCriteria extends AbstractFieldCriteria
{
    /**
     * Accepts a Visitor object
     *
     * @param IVisitor $visitor
     * @param mixed $valuePassed
     */
    public function acceptVisitor(IVisitor $visitor, &$valuePassed)
    {
        return $visitor->visitCriteriaIsField($this, $valuePassed);
    }
}
