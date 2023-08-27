<?php
namespace SabaiApps\Directories\Component\Entity\FieldType;

use SabaiApps\Directories\Component\Field;
use SabaiApps\Directories\Component\Field\Type\AbstractType;
use SabaiApps\Directories\Component\Field\IField;
use SabaiApps\Directories\Component\Field\Query;
use SabaiApps\Directories\Component\Entity\Type\IEntity;
use SabaiApps\Directories\Component\Entity\Model\Bundle;
use SabaiApps\Directories\Application;

class FeaturedFieldType extends AbstractType implements
    Field\Type\ISortable,
    Field\Type\IQueryable,
    Field\Type\ICopiable,
    Field\Type\IRestrictable,
    Field\Type\IConditionable
{
    protected function _fieldTypeInfo()
    {
        return array(
            'label' => __('Featured Item', 'directories'),
            'creatable' => false,
            'admin_only' => true,
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
                    'default' => 1,
                    'length' => 1,
                ),
                'featured_at' => array(
                    'type' => Application::COLUMN_INTEGER,
                    'notnull' => true,
                    'unsigned' => true,
                    'was' => 'featured_at',
                    'default' => 0,
                    'length' => 10,
                ),
                'expires_at' => array(
                    'type' => Application::COLUMN_INTEGER,
                    'notnull' => true,
                    'unsigned' => true,
                    'was' => 'expires_at',
                    'default' => 0,
                    'length' => 10,
                ),
            ),
            'indexes' => array(
                'value_featured_at' => array(
                    'fields' => array(
                        'value' => array('sorting' => 'ascending'),
                        'featured_at' => array('sorting' => 'ascending')
                    ),
                    'was' => 'value_featured_at',
                ),
                'expires_at' => array(
                    'fields' => array(
                        'expires_at' => array('sorting' => 'ascending'),
                    ),
                    'was' => 'expires_at',
                ),
            ),
        );
    }

    public function fieldTypeOnSave(IField $field, array $values, array $currentValues = null, array &$extraArgs = [])
    {
        $value = array_shift($values); // single entry allowed for this field
        if (!is_array($value)
            || empty($value['value'])
        ) {
            if (empty($currentValues)) return;

            $value = false; // delete
        } else {
            unset($value['enable']);
            $value['value'] = (int)$value['value'];
            if (empty($value['featured_at'])) {
                $value['featured_at'] = time(); 
                if (!empty($currentValues)) {
                    $current_value = array_shift($currentValues);
                    if (!empty($current_value['featured_at'])) {
                        $value['featured_at'] = $current_value['featured_at'];
                    }
                }  
            }
        }
        return array($value);
    }

    public function fieldTypeIsModified(IField $field, $valueToSave, $currentLoadedValue)
    {
        if (count($currentLoadedValue) !== count($valueToSave)
            || empty($valueToSave[0]) // may be false if removing
        ) return true;

        return count($currentLoadedValue[0]) !== count($valueToSave[0])
            || array_diff_assoc($currentLoadedValue[0], $valueToSave[0]);
    }
    
    public function fieldSortableOptions(IField $field)
    {
        return array(
            array('label' => __('Featured First', 'directories')),
        );
    }
    
    public function fieldSortableSort(Query $query, $fieldName, array $args = null)
    {
        $query->sortByField($fieldName, 'DESC');
    }
    
    public function fieldQueryableInfo(IField $field, $inAdmin = false)
    {
        return array(
            'example' => 1,
            'tip' => __('Enter 0 for non-featured items, 1 for all featured items, 5 for items with normal priority or higher, 9 for items with highest priority.', 'directories'),
        );
    }
    
    public function fieldQueryableQuery(Query $query, $fieldName, $paramStr, Bundle $bundle)
    {
        if ($priority = (int)$paramStr) {
            $query->fieldIsOrGreaterThan($fieldName, $priority);
        } else {
            $query->fieldIsNull($fieldName);
        }
    }
    
    public static function priorities()
    {
        return array(
            9 => _x('High', 'priority', 'directories'),
            5 => _x('Normal', 'priority', 'directories'),
            1 => _x('Low', 'priority', 'directories'),
        );
    }

    public function fieldCopyValues(IField $field, array $values, array &$allValue, $lang = null)
    {
        return $values;
    }

    public function fieldRestrictableOptions(IField $field)
    {
        $options = [
            '' => __('Featured/Non-Featured', 'directories'),
            -1 => __('Show all featured', 'directories'),
            -2 => __('Show all non-featured', 'directories'),
        ];
        foreach (self::priorities() as $key => $label) {
            $options[$key] = __('Priority', 'directories') . ': ' . $label;
        }
        return $options;
    }

    public function fieldRestrictableRestrict(IField $field, $value)
    {
        if ($value == -1) {
            return ['compare' => '>=', 'value' => 1];
        }
        if ($value == -2) {
            return ['compare' => 'NULL'];
        }
        return [];
    }

    public function fieldConditionableInfo(IField $field, $isServerSide = false)
    {
        if (!$isServerSide) return;

        return [
            '' => [
                'compare' => ['empty', 'filled'],
            ],
        ];
    }

    public function fieldConditionableRule(IField $field, $compare, $value = null, $name = '')
    {
        switch ($compare) {
            case 'empty':
                return ['type' => 'checked', 'value' => false];
            case 'filled':
                return ['type' => 'unchecked', 'value' => false];
            default:
                return;
        }
    }

    public function fieldConditionableMatch(IField $field, array $rule, array $values, IEntity $entity)
    {
        switch ($rule['type']) {
            case 'checked':
            case 'unchecked':
                $value = isset($values[0]['value']) ? $values[0]['value'] : null;
                return $rule['type'] === 'unchecked' ? empty($value) === $rule['value'] : !empty($value) === $rule['value'];
            default:
                return false;
        }
    }
}