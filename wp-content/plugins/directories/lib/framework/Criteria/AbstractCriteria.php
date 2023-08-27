<?php
namespace SabaiApps\Framework\Criteria;

abstract class AbstractCriteria
{
    const CRITERIA_AND = 'AND', CRITERIA_OR = 'OR';

    /**
     * Checks if the criteria is empty
     *
     * @return bool
     */
    public function isEmpty()
    {
        return false;
    }
}