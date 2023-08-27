<?php
namespace SabaiApps\Framework\Criteria;

class StartsWithCriteria extends AbstractValueCriteria
{
    /**
     * Accepts a Visitor object
     *
     * @param IVisitor $visitor
     * @param mixed $valuePassed
     */
    public function acceptVisitor(IVisitor $visitor, &$valuePassed)
    {
        return $visitor->visitCriteriaStartsWith($this, $valuePassed);
    }
}
