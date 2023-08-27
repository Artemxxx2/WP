<?php
namespace SabaiApps\Framework\Criteria;

class CompositeNotCriteria extends CompositeCriteria
{
    /**
     * Accepts a Visitor object
     *
     * @param IVisitor $visitor
     * @param mixed $valuePassed
     */
    public function acceptVisitor(IVisitor $visitor, &$valuePassed)
    {
        return $visitor->visitCriteriaCompositeNot($this, $valuePassed);
    }
}
