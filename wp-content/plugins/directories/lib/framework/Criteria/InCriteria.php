<?php
namespace SabaiApps\Framework\Criteria;

class InCriteria extends AbstractArrayCriteria
{
    /**
     * Accepts a Visitor object
     *
     * @param IVisitor $visitor
     * @param mixed $valuePassed
     */
    public function acceptVisitor(IVisitor $visitor, &$valuePassed)
    {
        return $visitor->visitCriteriaIn($this, $valuePassed);
    }
}
