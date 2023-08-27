<?php
namespace SabaiApps\Directories\Component\Field\Type;

use SabaiApps\Directories\Component\Entity\Type\IEntity;
use SabaiApps\Directories\Component\Entity\Model\Bundle;
use SabaiApps\Directories\Component\Field\IField;
use SabaiApps\Directories\Component\Field\Query;
use SabaiApps\Directories\Application;

class ChoiceType extends AbstractValueType
    implements ISortable, IQueryable, IHumanReadable, IConditionable, ILabellable, ISchemable
{
    protected function _fieldTypeInfo()
    {
        return array(
            'label' => __('Choice', 'directories'),
            'default_widget' => 'checkboxes',
            'default_settings' => array(
                'options' => null,
            ),
            'icon' => 'far fa-check-square',
        );
    }

    public function fieldTypeSettingsForm($fieldType, Bundle $bundle, array $settings, array $parents = [], array $rootParents = [])
    {
        return array(
            'options' => array(
                '#type' => 'options',
                '#title' => __('Options', 'directories'),
                '#default_value' => $settings['options'],
                '#multiple' => true,
                '#default_unchecked' => true,
                '#value_max_length' => 255,
                '#enable_color' => true,
            ),
        );
    }

    public function fieldTypeSchema()
    {
        return array(
            'columns' => array(
                'value' => array(
                    'type' => Application::COLUMN_VARCHAR,
                    'length' => 255,
                    'notnull' => true,
                    'was' => 'value',
                    'default' => '',
                ),
            ),
            'indexes' => array(
                'value' => array(
                    'fields' => array('value' => array('sorting' => 'ascending', 'length' => 191)),
                    'was' => 'value',
                ),
            ),
        );
    }
    
    public function fieldSortableOptions(IField $field)
    {
        return array(
            [],
            array('args' => array('desc'), 'label' => __('%s (desc)', 'directories'))
        );
    }
    
    public function fieldSortableSort(Query $query, $fieldName, array $args = null)
    {
        $query->sortByField($fieldName, isset($args) && $args[0] === 'desc' ? 'DESC' : 'ASC');
    }
    
    public function fieldQueryableInfo(IField $field, $inAdmin = false)
    {
        if ($inAdmin) {
            $tip = __('Enter values separated with commas.', 'directories');
        } else {
            $tip = __('Enter values separated with commas or "_current_" for values of current post/term if any.', 'directories');
        }
        return array(
            'example' => $this->_getFieldEntryExample($field),
            'tip' => $tip,
        );
    }
    
    protected function _getFieldEntryExample(IField $field)
    {
        $settings = $field->getFieldSettings();
        if (!empty($settings['options']['options'])) {
            return implode(',', array_slice(array_keys($settings['options']['options']), 0, 4));
        }
        return 'aaa,bb,cccc';
    }
    
    public function fieldQueryableQuery(Query $query, $fieldName, $paramStr, Bundle $bundle)
    {
        if ($params = $this->_queryableParams($paramStr)) {
            if (false !== $key = array_search('_current_', $params)) {
                unset($params[$key]);
                if (($entity = $this->_getCurrentEntity())
                    && ($values = $entity->getFieldValue($fieldName))
                ) {
                    foreach ($values as $value) $params[] = $value;
                }
            }
            $query->fieldIsIn($fieldName, $params);
        }
    }
    
    public function fieldHumanReadableText(IField $field, IEntity $entity, $separator = null, $key = null)
    {
        if (!$values = $entity->getFieldValue($field->getFieldName())) return '';
        
        $settings = $field->getFieldSettings();
        $ret = [];
        foreach ($values as $value) {
            if (isset($settings['options']['options'][$value])) {
                $ret[] = $settings['options']['options'][$value];
            }
        }
        return implode(isset($separator) ? $separator : ', ', $ret);
    }
    
    public function fieldConditionableInfo(IField $field, $isServerSide = false)
    {        
        return [
            '' => [
                'compare' => ['value', '!value', 'one', 'empty', 'filled'],
                'tip' => __('Enter values separated with commas.', 'directories'),
                'example' => $this->_getFieldEntryExample($field),
            ],
        ];
    }
    
    public function fieldConditionableRule(IField $field, $compare, $value = null, $name = '')
    {
        switch ($compare) {
            case 'value':
            case '!value':
            case 'one':
                $value = trim($value);
                if (strpos($value, ',')) {
                    if (!$value = explode(',', $value)) return;
                    
                    $value = array_map('trim', $value);
                }
                return ['type' => $compare, 'value' => $value];
            case 'empty':
                return ['type' => 'filled', 'value' => false];
            case 'filled':
                return ['type' => 'empty', 'value' => false];
            default:
                return;
        }
    }

    public function fieldConditionableMatch(IField $field, array $rule, array $values, IEntity $entity)
    {
        switch ($rule['type']) {
            case 'value':
            case '!value':
            case 'one':
                if (empty($values)) return $rule['type'] === '!value';

                foreach ((array)$rule['value'] as $rule_value) {
                    foreach ($values as $input) {
                        if (is_array($input)) {
                            $input = $input[$this->_valueColumn];
                        }
                        if ($input == $rule_value) {
                            if ($rule['type'] === '!value') return false;
                            if ($rule['type'] === 'one') return true;
                            continue 2;
                        }
                    }
                    // One of rules did not match
                    if ($rule['type'] === 'value') return false;
                }
                // All rules matched or did not match.
                return $rule['type'] !== 'one' ? true : false;
            case 'empty':
                return empty($values) === $rule['value'];
            case 'filled':
                return !empty($values) === $rule['value'];
            default:
                return false;
        }
    }

    public function fieldLabellableLabels(IField $field, IEntity $entity)
    {
        if (!$values = $entity->getFieldValue($field->getFieldName())) return;

        $settings = $field->getFieldSettings();
        $ret = [];
        foreach ($values as $value) {
            if (isset($settings['options']['options'][$value])) {
                $ret[] = $settings['options']['options'][$value];
            }
        }
        return $ret;
    }

    public function fieldSchemaProperties()
    {
        return ['priceRange'];
    }

    public function fieldSchemaRenderProperty(IField $field, $property, IEntity $entity)
    {
        if ((!$value = $entity->getSingleFieldValue($field->getFieldName()))
            || !isset($settings['options']['options'][$value])
        ) return;

        return $settings['options']['options'][$value];
    }
}
