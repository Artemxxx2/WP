<?php
namespace SabaiApps\Directories\Component\Entity\Type;

use SabaiApps\Directories\Component\Field;
use SabaiApps\Directories\Exception;
use SabaiApps\Framework\Criteria;

class FieldQuery extends Field\Query
{
    protected $_bundleName;

    public function taxonomyTermIdIs($taxonomyBundleType, $id, $ignoreAuto = true, $alias = null, $name = null)
    {
        $this->taxonomyTermIdIn($taxonomyBundleType, [$id], $ignoreAuto, $alias, $name);
    }

    public function taxonomyTermIdIn($taxonomyBundleType, array $ids, $ignoreAuto = false, $alias = null, $name = null)
    {
        $criteria_name = isset($name) ? $name : $taxonomyBundleType;
        if ($this->hasNamedCriteria($criteria_name)) {
            foreach ($this->getNamedCriteria($criteria_name) as $criteria) {
                if ($criteria instanceof Criteria\InCriteria) {
                    foreach ($criteria->getArray() as $_term_id) {
                        $ids[] = $_term_id;
                    }
                } elseif ($criteria instanceof Criteria\IsCriteria) {
                    $ids[] = $criteria->getValue();
                }
            }
            $this->removeNamedCriteria($criteria_name);
        }
        $this->fieldIsIn($taxonomyBundleType, $ids, 'value', $alias, null, $name);
        // whether or not to ignore terms automatically added by the system, such as parent terms that were not selected by the user explicitly 
        if ($ignoreAuto) {
            $this->fieldIsNot($taxonomyBundleType, true, 'auto', $alias, null, $name);
        }
    }
    
    public function taxonomyTermTitleContains($taxonomyBundleName, $taxonomyBundleType, $string)
    {
        throw new Exception\RuntimeException('Call to unsupported method: ' . __METHOD__);
    }

    public function fieldIs($field, $value, $column = 'value', $alias = null, $on = null, $name = null)
    {
        if ($field === 'bundle_name') {
            $this->_bundleName = $value;
        }
        return parent::fieldIs($field, $value, $column, $alias, $on, $name);
    }

    public function fieldIsIn($field, array $values, $column = 'value', $alias = null, $on = null, $name = null)
    {
        if ($field === 'bundle_name') {
            $this->_bundleName = array_values($values);
        }
        return parent::fieldIsIn($field, $values, $column, $alias, $on, $name);
    }

    public function getQueriedBundleName()
    {
        return $this->_bundleName;
    }
}