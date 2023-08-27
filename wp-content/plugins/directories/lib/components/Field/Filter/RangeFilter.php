<?php
namespace SabaiApps\Directories\Component\Field\Filter;

use SabaiApps\Directories\Component\Field\IField;
use SabaiApps\Directories\Component\Field\Query;
use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Form;

class RangeFilter extends AbstractFilter implements IConditionable
{
    protected $_maxSuffix = null;

    protected function _fieldFilterInfo()
    {
        return [
            'label' => __('Slider input field', 'directories'),
            'field_types' => ['number', 'range', 'price'],
            'default_settings' => [
                'ignore_min_max' => true,
            ],
        ];
    }

    public function fieldFilterSettingsForm(IField $field, array $settings, array $parents = [])
    {
        return [
            '#element_validate' => [function(Form\Form $form, &$value, $element) use ($field, $settings) {
                if (empty($value['step'])) return;

                $min = $this->_getMinSetting($field, $value);
                $max = $this->_getMaxSetting($field, $value);

                $range = $max - $min;
                $i = $range / $value['step'];
                if ($i <= 0
                    || $range - (floor($i) * $value['step']) > 0
                ) {
                    $form->setError(sprintf(__('The full specified value range of the slider (%s - %s) should be evenly divisible by the step', 'directories'), $min, $max), $element['#name'] . '[step]');
                }
            }],
            'step' => [
                '#type' => 'number',
                '#title' => __('Slider step', 'directories'),
                '#default_value' => isset($settings['step']) ? $settings['step'] : $this->_getDefaultStep($field),
                '#size' => 5,
                '#numeric' => true,
                '#min_value' => 0,
                '#weight' => 5,
            ],
            'ignore_min_max' => [
                '#type' => 'checkbox',
                '#title' => __('Do not filter if min/max values are selected', 'directories'),
                '#default_value' => !empty($settings['ignore_min_max']),
                '#weight' => 10,
            ],
        ];
    }

    protected function _getMinSetting(IField $field, array $settings)
    {
        $field_settings = $field->getFieldSettings();
        return isset($field_settings['min']) ? $field_settings['min'] : 0;
    }

    protected function _getMaxSetting(IField $field, array $settings)
    {
        $field_settings = $field->getFieldSettings();
        return isset($field_settings['max']) ? $field_settings['max'] : 100;
    }
    
    protected function _getDefaultStep(IField $field)
    {
        $settings = $field->getFieldSettings();
        return empty($settings['decimals']) ? 1 : ($settings['decimals'] == 1 ? 0.1 : 0.01);
    }
    
    public function fieldFilterForm(IField $field, $filterName, array $settings, $request = null, Entity\Type\Query $query = null, array $current = null, $autoSubmit = true, array $parents = [])
    {
        $prefix_suffix = $this->_getFieldPrefixSuffix($field);
        return [
            '#type' => 'range',
            '#min_value' =>  $this->_getMinSetting($field, $settings),
            '#max_value' => $this->_getMaxSetting($field, $settings),
            '#numeric' => true,
            '#field_prefix' => strlen($prefix_suffix[0]) ? $prefix_suffix[0] : null,
            '#field_suffix' => strlen($prefix_suffix[1]) ? $prefix_suffix[1] : null,
            '#step' => !empty($settings['step']) ? $settings['step'] : $this->_getDefaultStep($field),
            '#slider_max_postfix' => $this->_maxSuffix,
            '#entity_filter_form_type' => 'slider',
        ];
    }
    
    public function fieldFilterIsFilterable(IField $field, array $settings, &$value, array $requests = null)
    {
        if (!strlen($value)) return false;

        if (strpos($value, '%3B')) { // The value may come encoded for some reason on Safari
            $value = str_replace('%3B', ';', $value);
        }
        if (!$_value = explode(';', $value)) return false;

        $_value[0] = isset($_value[0]) ? (string)$_value[0] : '';
        $_value[1] = isset($_value[1]) ? (string)$_value[1] : '';
        if (!empty($settings['ignore_min_max'])) {
            if (strlen($_value[0])
                && strlen($_value[1])
            ) {
                $min = $this->_getMinSetting($field, $settings);
                $max = $this->_getMaxSetting($field, $settings);
                if ($_value[0] == $min
                    && $_value[1] == $max
                ) {
                    return false;
                }
                return true;
            }
        }
        
        return strlen($_value[0]) || strlen($_value[1]);
    }
    
    public function fieldFilterDoFilter(Query $query, IField $field, array $settings, $value, array &$sorts)
    {
        if (!isset($value['min'])) {
            $value['min'] = $this->_getMinSetting($field, $settings);
        }
        if (!isset($value['max'])) {
            $value['max'] = $this->_getMaxSetting($field, $settings);
        }
        $this->_fieldFilterDoFilter($query, $field, $settings, $value['min'], $value['max'], $sorts);
    }

    protected function _fieldFilterDoFilter(Query $query, IField $field, array $settings, $min, $max, array &$sorts)
    {
        if ($field->getFieldType() === 'range') {
            $query->fieldIsOrGreaterThan($field, $min, 'min')
                ->fieldIsOrSmallerThan($field, $max, 'max');
        } else {
            $query->fieldIsOrGreaterThan($field, $min)
                ->fieldIsOrSmallerThan($field, $max);
        }
    }

    protected function _getDefaultLabel($defaultLabel, array $settings)
    {
        return $defaultLabel;
    }

    public function fieldFilterLabels(IField $field, array $settings, $value, $form, $defaultLabel)
    {
        $prefix_suffix = $this->_getFieldPrefixSuffix($field);
        if (!isset($value['min'])) $value['min'] = $this->_getMinSetting($field, $settings);
        if (!isset($value['max'])) $value['max'] = $this->_getMaxSetting($field, $settings);
        $label = $this->_getDefaultLabel($defaultLabel, $settings);

        return ['' => $this->_application->H($label . ': ' . $prefix_suffix[0] . $value['min'] . ' - ' . $value['max'] . $prefix_suffix[1])];
    }

    public function fieldFilterConditionableInfo(IField $field)
    {
        return [
            '' => [
                'compare' => ['<value', '>value', '<>value'],
                'tip' => __('Enter a single numeric value or two numeric values separated with a comma', 'directories'),
                'example' => 3,
            ],
        ];
    }

    public function fieldFilterConditionableRule(IField $field, $filterName, array $settings, $compare, $value = null, $name = '')
    {
        switch ($compare) {
            case '<value':
            case '>value':
                return is_numeric($value) ? ['type' => $compare, 'value' => $value] : null;
            case '<>value':
                if (!strpos($value, ',')
                    || (!$value= explode(',', $value))
                    || !is_numeric($value[0])
                    || !is_numeric($value[1])
                ) return;

                return ['type' => $compare, 'value' => $value[0] . ',' . $value[1]];
            default:
        }
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
