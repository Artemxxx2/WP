<?php
namespace SabaiApps\Directories\Component\Field\Type;

use SabaiApps\Directories\Component\Field\IField;
use SabaiApps\Directories\Component\Field\Query;
use SabaiApps\Directories\Component\Entity\Type\IEntity;
use SabaiApps\Directories\Component\Entity\Model\Bundle;
use SabaiApps\Directories\Application;

class RangeType extends AbstractType implements IQueryable, ISchemable, IHumanReadable, ICopiable, IConditionable
{
    protected function _fieldTypeInfo()
    {
        return array(
            'label' => __('Range', 'directories'),
            'default_settings' => array(
                'min' => null,
                'max' => null,
                'decimals' => 0,
                'prefix' => null,
                'suffix' => null,
            ),
            'icon' => 'fas fa-sliders-h',
        );
    }

    public function fieldTypeSettingsForm($fieldType, Bundle $bundle, array $settings, array $parents = [], array $rootParents = [])
    {
        return array(
            '#element_validate' => array(array(array($this, 'validateMinMaxSettings'), array('decimals'))),
            'min' => array(
                '#type' => 'number',
                '#title' => __('Minimum', 'directories'),
                '#description' => __('The minimum value allowed in this field.', 'directories'),
                '#size' => 10,
                '#default_value' => $settings['min'],
                '#numeric' => true,
                '#step' => 0.01,
            ),
            'max' => array(
                '#type' => 'number',
                '#title' => __('Maximum', 'directories'),
                '#description' => __('The maximum value allowed in this field.', 'directories'),
                '#size' => 10,
                '#default_value' => $settings['max'],
                '#numeric' => true,
                '#step' => 0.01,
            ),
            'decimals' => array(
                '#type' => 'select',
                '#title' => __('Decimals', 'directories'),
                '#description' => __('The number of digits to the right of the decimal point.', 'directories'),
                '#options' => array(0 => __('0 (no decimals)', 'directories'), 1 => 1, 2 => 2),
                '#default_value' => $settings['decimals'],
            ),
            'prefix' => array(
                '#type' => 'textfield',
                '#title' => __('Field prefix', 'directories'),
                '#description' => __('Example: $, #, -', 'directories'),
                '#size' => 20,
                '#default_value' => $settings['prefix'],
                '#no_trim' => true,
            ),
            'suffix' => array(
                '#type' => 'textfield',
                '#title' => __('Field suffix', 'directories'),
                '#description' => __('Example: km, %, g', 'directories'),
                '#size' => 20,
                '#default_value' => $settings['suffix'],
                '#no_trim' => true,
            ),
        );
    }

    public function fieldTypeSchema()
    {
        return array(
            'columns' => array(
                'min' => array(
                    'type' => Application::COLUMN_DECIMAL,
                    'notnull' => true,
                    'length' => 18,
                    'scale' => 2,
                    'unsigned' => false,
                    'was' => 'min',
                    'default' => 0,
                ),
                'max' => array(
                    'type' => Application::COLUMN_DECIMAL,
                    'notnull' => true,
                    'length' => 18,
                    'scale' => 2,
                    'unsigned' => false,
                    'was' => 'max',
                    'default' => 0,
                ),
            ),
            'indexes' => array(
                'min' => array(
                    'fields' => array('min' => array('sorting' => 'ascending')),
                    'was' => 'min',
                ),
                'max' => array(
                    'fields' => array('max' => array('sorting' => 'ascending')),
                    'was' => 'max',
                ),
            ),
        );
    }

    public function fieldTypeOnLoad(IField $field, array &$values, IEntity $entity, array $allValues)
    {
        foreach (array_keys($values) as $key) {
            $values[$key]['min'] = (float)$values[$key]['min'];
            $values[$key]['max'] = (float)$values[$key]['max'];
        }
    }

    public function fieldTypeOnSave(IField $field, array $values, array $currentValues = null, array &$extraArgs = [])
    {
        $settings = $field->getFieldSettings();
        $ret = [];
        foreach ($values as $weight => $value) {
            if (!is_array($value)
                || !is_numeric(@$value['min'])
                || !is_numeric(@$value['max'])
            ) continue;

            $ret[] = array(
                'min' => round($value['min'], $settings['decimals']),
                'max' => round($value['max'], $settings['decimals']),
            );
        }

        return $ret;
    }

    public function fieldQueryableInfo(IField $field, $inAdmin = false)
    {
        return array(
            'example' => '1,10',
            'tip' => __('Enter a single number for exact match, two numbers separated with a comma for range search.', 'directories'),
        );
    }
    
    public function fieldQueryableQuery(Query $query, $fieldName, $paramStr, Bundle $bundle)
    {
        $params = $this->_queryableParams($paramStr, false);
        switch (count($params)) {
            case 0:
                break;
            case 1:
                if (strlen($params[0])) {
                    $query->fieldIsOrGreaterThan($fieldName, $params[0], 'min')
                        ->fieldIsOrSmallerThan($fieldName, $params[0], 'max');
                }
                break;
            default:
                if (strlen($params[0])) {
                    $query->fieldIsOrGreaterThan($fieldName, $params[0], 'min');
                }
                if (strlen($params[1])) {
                    $query->fieldIsOrSmallerThan($fieldName, $params[1], 'max');
                }
        }
    }

    public function fieldConditionableInfo(IField $field, $isServerSide = false)
    {
        return [
            '' => [
                'compare' => $isServerSide ? ['<value', '>value', '<>value', 'empty', 'filled'] : ['<value', '>value', '<>value'],
                'tip' => __('Enter a single numeric value or multiple numeric values separated with a comma', 'directories'),
                'example' => '10,25',
            ],
        ];
    }

    public function fieldConditionableRule(IField $field, $compare, $value = null, $name = '')
    {
        switch ($compare) {
            case '<value':
            case '>value':
                return is_numeric($value) ? ['type' => $compare, 'value' => $value] : null;
            case '<>value':
                if (strpos($value, ',')
                    && ($values = explode(',', $value))
                    && is_numeric($values[0])
                    && is_numeric($values[1])
                ) {
                    return ['type' => $compare, 'value' => $values[0] . ',' . $values[1]];
                }
            case 'empty':
                return ['type' => 'filled', 'value' => false];
            case 'filled':
                return ['type' => 'empty', 'value' => false];
            default:
        }
    }

    public function fieldConditionableMatch(IField $field, array $rule, array $values, IEntity $entity)
    {
        switch ($rule['type']) {
            case '<value':
            case '>value':
                if (!empty($values)) {
                    foreach ($values as $input) {
                        foreach ((array)$rule['value'] as $rule_value) {
                            if ($rule['type'] === '<value') {
                                if ($input['max'] < $rule_value) return true;
                            } else {
                                if ($input['min'] > $rule_value) return true;
                            }
                        }
                    }
                }
                return false;
            case '<>value':
                if (!empty($values)) {
                    foreach ($values as $input) {
                        foreach ((array)$rule['value'] as $rule_value) {
                            if (!strpos($rule_value, ',')
                                || (!$rule_value = explode(',', $rule_value))
                                || count($rule_value) < 2
                            ) continue;

                            if ($input['min'] >= $rule_value[0]
                                && $input['max'] <= $rule_value[1]
                            ) return true;
                        }
                    }
                }
                return false;
            case 'empty':
                return empty($values) === $rule['value'];
            case 'filled':
                return !empty($values) === $rule['value'];
            default:
                return false;
        }
    }
    
    public function fieldSchemaProperties()
    {
        return array('priceRange');
    }
    
    public function fieldSchemaRenderProperty(IField $field, $property, IEntity $entity)
    {        
        if (!$value = $entity->getSingleFieldValue($field->getFieldName())) return;

        return $this->_getFormattedValues($field, $entity, [$value]);
    }

    public function fieldHumanReadableText(IField $field, IEntity $entity, $separator = null, $key = null)
    {
        if (!$values = $this->_getFormattedValues($field, $entity, null, $key)) return '';

        return implode(isset($separator) ? $separator : ', ', $values);
    }

    protected function _getFormattedValues(IField $field, IEntity $entity, array $values = null, $key = null)
    {
        if (!isset($values)
            && (!$values = $entity->getFieldValue($field->getFieldName()))
        ) return;

        $settings = $field->getFieldSettings();
        $decimals = empty($settings['decimals']) ? 0 : $settings['decimals'];
        $prefix = isset($settings['prefix']) ? $settings['prefix'] : '';
        $suffix = isset($settings['suffix']) ? $settings['suffix'] : '';
        if (isset($key)
            && ($key === 'min' || $key === 'max')
        ) {
            foreach (array_keys($values) as $i) {
                $value = $this->_application->getPlatform()->numberFormat($values[$i][$key], $decimals);
                $values[$i] = $prefix . $value . $suffix;
            }
        } else {
            foreach (array_keys($values) as $i) {
                $min = $this->_application->getPlatform()->numberFormat($values[$i]['min'], $decimals);
                $max = $this->_application->getPlatform()->numberFormat($values[$i]['max'], $decimals);
                $values[$i] = $prefix . $min . $suffix . ' - ' . $prefix . $max . $suffix;
            }
        }
        return $values;
    }

    public function fieldCopyValues(IField $field, array $values, array &$allValue, $lang = null)
    {
        return $values;
    }
}