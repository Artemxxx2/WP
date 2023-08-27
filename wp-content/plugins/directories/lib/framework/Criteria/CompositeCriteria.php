<?php
namespace SabaiApps\Framework\Criteria;

class CompositeCriteria extends AbstractCriteria
{
    /**
     * @var array
     */
    protected $_elements = [];
    /**
     * @var array
     */
    protected $_conditions = [];
    /**
     * @var int
     */
    protected $_index = -1;

    /**
     * @param array $elements
     */
    public function __construct(array $elements = [], $condition = AbstractCriteria::CRITERIA_AND)
    {
        if (!empty($elements)) {
            if ($condition === AbstractCriteria::CRITERIA_OR) {
                foreach (array_keys($elements) as $i) {
                    $this->addOr($elements[$i]);
                }
            } else {
                foreach (array_keys($elements) as $i) {
                    $this->addAnd($elements[$i]);
                }
            }
        }
    }

    /**
     * @return array
     */
    public function getElements()
    {
        return $this->_elements;
    }

    /**
     * @return array
     */
    public function getConditions()
    {
        return $this->_conditions;
    }

    /**
     * @param AbstractCriteria $criteria
     */
    public function addAnd(AbstractCriteria $criteria)
    {
        ++$this->_index;
        $this->_elements[$this->_index] = $criteria;
        $this->_conditions[$this->_index] = AbstractCriteria::CRITERIA_AND;
        return $this;
    }

    /**
     * @param AbstractCriteria $criteria
     */
    public function addOr(AbstractCriteria $criteria)
    {
        ++$this->_index;
        $this->_elements[$this->_index] = $criteria;
        $this->_conditions[$this->_index] = AbstractCriteria::CRITERIA_OR;
        return $this;
    }

    /**
     * @return bool
     */
    public function isEmpty()
    {
        return empty($this->_elements);
    }

    /**
     * Accepts a Visitor object
     *
     * @param Visitor $visitor
     * @param mixed $valuePassed
     */
    public function acceptVisitor(IVisitor $visitor, &$valuePassed)
    {
        return $visitor->visitCriteriaComposite($this, $valuePassed);
    }
    
    /**
     * @return int
     */
    public function getIndex()
    {
        return $this->_index;
    }

    /**
     * @return AbstractCriteria
     */
    public function get($index)
    {
        return $this->_elements[$index];
    }
    
    public function remove($index = null, $return = false)
    {
        if (isset($index)) {
            if ($return) {
                $ret = $this->_elements[$index];
                unset($this->_elements[$index], $this->_conditions[$index]);
                return $ret;
            }
            unset($this->_elements[$index], $this->_conditions[$index]);
        } else {
            $this->_elements = $this->_conditions = [];
        }
        return $this;
    }
}
