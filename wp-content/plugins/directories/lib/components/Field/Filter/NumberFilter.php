<?php
namespace SabaiApps\Directories\Component\Field\Filter;

use SabaiApps\Directories\Component\Field\IField;
use SabaiApps\Directories\Component\Field\Query;
use SabaiApps\Directories\Component\Entity;

class NumberFilter extends AbstractFilter implements IConditionable
{
    use ConditionableNumberTrait;

    protected function _fieldFilterInfo()
    {
        return [
            'label' => __('Text input field', 'directories'),
            'field_types' => ['number', 'range', 'price'],
            'default_settings' => [],
        ];
    }

    public function fieldFilterSettingsForm(IField $field, array $settings, array $parents = [])
    {
        if (!in_array($field->getFieldType(), ['number', 'price'])) return;

        return [
            'match' => [
                '#type' => 'select',
                '#title' => __('Match type', 'directories'),
                '#options' => [
                    '=' => __('Exact match', 'directories'),
                    '>=' => __('Include greater values', 'directories'),
                    '<=' => __('Include smaller values', 'directories'),
                ],
                '#default_value' => isset($settings['match']) ? $settings['match'] : '=',
            ],
        ];
    }

    public function fieldFilterForm(IField $field, $filterName, array $settings, $request = null, Entity\Type\Query $query = null, array $current = null, $autoSubmit = true, array $parents = [])
    {
        $field_settings = $field->getFieldSettings();
        if ($field->getFieldType() !== 'price'
            && $field_settings['decimals'] > 0
        ) {
            $numeric = true;
            $integer = false;
            $min_value = isset($field_settings['min']) && is_numeric($field_settings['min']) ? $field_settings['min'] : null;
            $max_value = isset($field_settings['max']) && is_numeric($field_settings['max']) ? $field_settings['max'] : null;
            $step = $field_settings['decimals'] == 1 ? 0.1 : 0.01;
        } else {
            $numeric = false;
            $integer = true;
            $min_value = isset($field_settings['min']) ? intval($field_settings['min']) : null;
            $max_value = isset($field_settings['max']) ? intval($field_settings['max']) : null;
            $step = null;
        }
        $prefix_suffix = $this->_getFieldPrefixSuffix($field);
        return [
            '#type' => 'number',
            '#min_value' => $min_value,
            '#max_value' => $max_value,
            '#integer' => $integer,
            '#numeric' => $numeric,
            '#field_prefix' => strlen($prefix_suffix[0]) ? $prefix_suffix[0] : null,
            '#field_suffix' => strlen($prefix_suffix[1]) ? $prefix_suffix[1] : null,
            '#step' => $step,
            '#entity_filter_form_type' => 'textfield',
        ];
    }
    
    public function fieldFilterIsFilterable(IField $field, array $settings, &$value, array $requests = null)
    {
        return strlen((string)@$value) > 0;
    }
    
    public function fieldFilterDoFilter(Query $query, IField $field, array $settings, $value, array &$sorts)
    {
        if (in_array($field->getFieldType(), ['number', 'price'])) {
            switch (@$settings['match']) {
                case '>=':
                    $query->fieldIsOrGreaterThan($field, $value);
                    break;
                case '<=':
                    $query->fieldIsOrSmallerThan($field, $value);
                    break;
                default:
                    $query->fieldIs($field, $value);
            }
        } else {
            $query->fieldIsOrSmallerThan($field, $value, 'min')
                ->fieldIsOrGreaterThan($field, $value, 'max');
        }
    }
    
    public function fieldFilterLabels(IField $field, array $settings, $value, $form, $defaultLabel)
    {
        $prefix_suffix = $this->_getFieldPrefixSuffix($field);
        return ['' => $this->_application->H($defaultLabel . ': ' . $prefix_suffix[0] . $value . $prefix_suffix[1])];
    }

    protected function _getFieldPrefixSuffix(IField $field)
    {
        $field_settings = $field->getFieldSettings();
        $prefix = $suffix = '';
        if ($field->getFieldType() === 'price') {
            $currencies = $field_settings['currencies'];
            if (count($currencies) === 1) {
                if (($format = $this->_application->System_Currency_formats($currencies[0]))
                    && isset($format[2])
                ) {
                    if (empty($format[1])) {
                        $prefix = $format[2];
                    } else {
                        $suffix = $format[2];
                    }
                }
            }
        } else {
            if (isset($field_settings['prefix']) && strlen($field_settings['prefix'])) {
                $prefix = $field_settings['prefix'];
            }
            if (isset($field_settings['suffix']) && strlen($field_settings['suffix'])) {
                $suffix = $field_settings['suffix'];
            }
        }

        return [$prefix, $suffix];
    }
}