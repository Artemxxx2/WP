<?php
namespace SabaiApps\Directories\Component\Entity\FieldType;

use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Field;
use SabaiApps\Directories\Application;

class ChildCountFieldType extends Field\Type\AbstractType implements
    Field\Type\ISortable,
    Field\Type\IQueryable
{
    protected function _fieldTypeInfo()
    {
        return array(
            'label' => 'Child Entity Count',
            'creatable' => false,
        );
    }

    public function fieldTypeSchema()
    {
        return array(
            'columns' => array(
                'value' => array(
                    'type' => Application::COLUMN_INTEGER,
                    'notnull' => true,
                    'unsigned' => true,
                    'was' => 'value',
                    'default' => 0,
                    'length' => 10,
                ),
                'child_bundle_type' => array(
                    'type' => Application::COLUMN_VARCHAR,
                    'notnull' => true,
                    'length' => 40,
                    'was' => 'child_bundle_type',
                    'default' => '',
                ),
            ),
            'indexes' => array(
                'value' => array(
                    'fields' => array('value' => array('sorting' => 'ascending')),
                    'was' => 'value',
                ),
            ),
        );
    }

    public function fieldTypeOnSave(Field\IField $field, array $values, array $currentValues = null, array &$extraArgs = [])
    {
        $ret = [];
        foreach ($values as $value) {
            if (!is_array($value)) {
                $ret[] = false; // delete
            } else {
                $ret[] = $value;
            }
        }
        return $ret;
    }

    public function fieldTypeOnLoad(Field\IField $field, array &$values, Entity\Type\IEntity $entity, array $allValues)
    {
        $new_values = [];
        foreach ($values as $value) {
            // Index by child bundle name for easier access to counts
            $new_values[0][$value['child_bundle_type']] = (int)$value['value'];
        }
        $values = $new_values;
    }
    
    public function fieldTypeIsModified(Field\IField $field, $valueToSave, $currentLoadedValue)
    {
        $current = $new = [];
        if (!empty($currentLoadedValue[0])) {
            foreach ($currentLoadedValue[0] as $child_bundle_name => $value) {
                $current[] = array('value' => $value, 'child_bundle_type' => $child_bundle_name);
            }
        }
        foreach ($valueToSave as $value) {
            $new[] = array('value' => (int)$value['value'], 'child_bundle_type' => $value['child_bundle_type']);
        }
        return $current !== $new;
    }
    
    public function fieldSortableOptions(Field\IField $field)
    {
        if ((!$bundle = $this->_application->Entity_Bundle($field->bundle_name))
            || (!$child_bundle_types = $this->_application->Entity_BundleTypes_children($bundle->type))
        ) return false;
        
        $ret = [];
        foreach ($child_bundle_types as $child_bundle_type) {
            if (!$child_bundle = $this->_application->Entity_Bundle($child_bundle_type, $bundle->component, $bundle->group)) continue;
                    
            $ret[] = array(
                'label' => sprintf(_x('Most %s', 'sort option label', 'directories'), $child_bundle->getLabel()),
                'args' => array($child_bundle_type),
            );
            $ret[] = array(
                'label' => sprintf(_x('Least %s', 'sort option label', 'directories'), $child_bundle->getLabel()),
                'args' => array($child_bundle_type, 'asc'),
            );
        }
        
        return empty($ret) ? false : $ret;
    }
    
    public function fieldSortableSort(Field\Query $query, $fieldName, array $args = null)
    {
        if (!isset($args[0])) return;
        
        $query->startCriteriaGroup('OR')
            ->fieldIs($fieldName, $args[0], 'child_bundle_type')
            ->fieldIsNull($fieldName, 'child_bundle_type') // include those without any child bundle count
            ->finishCriteriaGroup()
            ->sortByField($fieldName, isset($args[1]) && $args[1] === 'asc' ? 'ASC' : 'DESC', 'value', null, 0);
    }

    public function fieldQueryableInfo(Field\IField $field, $inAdmin = false)
    {
        return array(
            'example' => 'directory_rev_rev,5',
            'tip' => __('Enter a child content type name and up to two numbers separated with a comma for range search.', 'directories'),
        );
    }
    
    public function fieldQueryableQuery(Field\Query $query, $fieldName, $paramStr, Entity\Model\Bundle $bundle)
    {
        $params = $this->_queryableParams($paramStr);
        if (!empty($params[1]) || !empty($params[2])) {
            $query->fieldIs($fieldName, $params[0], 'child_bundle_type');
            if (!empty($params[1])) {
                $query->fieldIsOrGreaterThan($fieldName, $params[1]);
            }
            if (!empty($params[2])) {
                $query->fieldIsOrSmallerThan($fieldName, $params[2]);
            }
        }
    }
}