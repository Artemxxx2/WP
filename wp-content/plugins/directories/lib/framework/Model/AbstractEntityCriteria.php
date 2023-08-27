<?php
namespace SabaiApps\Framework\Model;

use SabaiApps\Framework\Criteria;
use SabaiApps\Framework\Exception;

abstract class AbstractEntityCriteria extends Criteria\CompositeCriteria
{
    private $_andOr;
    protected $_name, $_keys = [];
    
    public function __construct($name)
    {
        parent::__construct();
        $this->_name = $name;
    }
    
    public function getName()
    {
        return $this->_name;
    }

    /**
     * Appends a new criteria
     *
     * @param Criteria\AbstractCriteria $criteria
     */
    public function add(Criteria\AbstractCriteria $criteria)
    {
        switch ($this->_andOr) {
            case Criteria\AbstractCriteria::CRITERIA_OR:
                $this->addOr($criteria);
                $this->_andOr = Criteria\AbstractCriteria::CRITERIA_AND;
                break;
            case Criteria\AbstractCriteria::CRITERIA_AND:
            default:
                $this->addAnd($criteria);
                break;
        }
        return $this;
    }

    /**
     * Adds an AND condition to the criteria
     * @return AbstractEntityCriteria
     */
    public function and_()
    {
        $this->_andOr = Criteria\AbstractCriteria::CRITERIA_AND;
        return $this;
    }

    /**
     * Adds an OR condition to the criteria
     * @return AbstractEntityCriteria
     */
    public function or_()
    {
        $this->_andOr = Criteria\AbstractCriteria::CRITERIA_OR;
        return $this;
    }

    /**
     * Magically adds a new criteria
     * @param string $method
     * @param array $args
     * @return AbstractEntityCriteria
     */
    public function __call($method, $args)
    {
        $parts = explode('_', $method);
        $key = $parts[0];
        $type = $parts[1];
        if (isset($this->_keys[$key])) {
            $field = $this->_keys[$key];
            // If second key is set, check if it has a valid field
            if (isset($parts[2])
                && isset($this->_keys[$parts[2]])
            ) {
                $field2 = $this->_keys[$parts[2]];
                switch ($type) {
                    case 'is':
                        return $this->add(new Criteria\IsFieldCriteria($field, $field2));
                    case 'isNot':
                        return $this->add(new Criteria\IsNotFieldCriteria($field, $field2));
                    case 'isGreaterThan':
                        return $this->add(new Criteria\IsGreaterThanFieldCriteria($field));
                    case 'isSmallerThan':
                        return $this->add(new Criteria\IsSmallerThanFieldCriteria($field, $field2));
                    case 'isOrGreaterThan':
                        return $this->add(new Criteria\IsOrGreaterThanFieldCriteria($field, $field2));
                    case 'isOrSmallerThan':
                        return $this->add(new Criteria\IsOrSmallerThanFieldCriteria($field, $field2));
                }
            } else {
                switch ($type) {
                    case 'is':
                        return $this->add(new Criteria\IsCriteria($field, $args[0]));
                    case 'isNot':
                        return $this->add(new Criteria\IsNotCriteria($field, $args[0]));
                    case 'isGreaterThan':
                        return $this->add(new Criteria\IsGreaterThanCriteria($field, $args[0]));
                    case 'isSmallerThan':
                        return $this->add(new Criteria\IsSmallerThanCriteria($field, $args[0]));
                    case 'isOrGreaterThan':
                        return $this->add(new Criteria\IsOrGreaterThanCriteria($field, $args[0]));
                    case 'isOrSmallerThan':
                        return $this->add(new Criteria\IsOrSmallerThanCriteria($field, $args[0]));
                    case 'in':
                        return $this->add(new Criteria\InCriteria($field, $args[0]));
                    case 'notIn':
                        return $this->add(new Criteria\NotInCriteria($field, $args[0]));
                    case 'startsWith':
                        return $this->add(new Criteria\StartsWithCriteria($field, $args[0]));
                    case 'endsWith':
                        return $this->add(new Criteria\EndsWithCriteria($field, $args[0]));
                    case 'contains':
                        return $this->add(new Criteria\ContainsCriteria($field, $args[0]));
                    case 'isNull':
                        return $this->add(new Criteria\IsNullCriteria($field));
                    case 'isNotNull':
                        return $this->add(new Criteria\IsNotNullCriteria($field));
                }
            }
        }

        throw new Exception(sprintf('Call to undefined method %s', $method));
    }
}