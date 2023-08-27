<?php
namespace SabaiApps\Directories\Component\Field\Filter;

use SabaiApps\Directories\Component\Field\IField;
use SabaiApps\Directories\Component\Field\Query;
use SabaiApps\Directories\Component\Entity;

class TextRangeFilter extends AbstractFilter implements IConditionable
{
    protected function _fieldFilterInfo()
    {
        return [
            'label' => __('Text range input field', 'directories'),
            'field_types' => ['number', 'range'],
        ];
    }
    
    public function fieldFilterForm(IField $field, $filterName, array $settings, $request = null, Entity\Type\Query $query = null, array $current = null, $autoSubmit = true, array $parents = [])
    {
        $field_settings = $field->getFieldSettings();
        $min = isset($field_settings['min']) ? $field_settings['min'] : null;
        $max = isset($field_settings['max']) ? $field_settings['max'] : null;
        $ret = [
            '#entity_filter_form_type' => 'textfield',
            '#group' => true,
            'min' => [
                '#type' => 'text',
                '#placeholder' => __('Min', 'directories'),
                '#class' => 'drts-view-filter-ignore',
                '#weight' => 1,
                '#prefix' => '<div class="drts-row drts-gutter-none"><div class="drts-col-10 drts-view-filter-trigger-main"><div class="drts-row drts-gutter-xs"><div class="drts-col-6">',
                '#suffix' => '</div>',
                '#min_value' => $min,
                '#max_value' => $max,
                '#numeric' => true,
            ],
            'max' => [
                '#type' => 'text',
                '#placeholder' => __('Max', 'directories'),
                '#class' => 'drts-view-filter-ignore',
                '#weight' => 2,
                '#prefix' => '<div class="drts-col-6">',
                '#suffix' => '</div></div></div>',
                '#min_value' => $min,
                '#max_value' => $max,
                '#numeric' => true,
            ],
            'button' => array(
                '#prefix' => '<div class="drts-col-2 drts-view-filter-trigger-btn">',
                '#suffix' => '</div></div>',
                '#type' => 'markup',
                '#markup' => '<button type="button" class="' . DRTS_BS_PREFIX . 'btn ' . DRTS_BS_PREFIX . 'btn-link ' . DRTS_BS_PREFIX . 'btn-block drts-view-filter-trigger">' .
                    '<i class="fas fa-fw fa-search"></i></button>',
                '#weight' => 3,
            ),
        ];

        return $ret;
    }
    
    public function fieldFilterIsFilterable(IField $field, array $settings, &$value, array $requests = null)
    {
        $min = isset($value['min']) && strlen($value['min']) ? $value['min'] : null;
        $max = isset($value['max']) && strlen($value['max']) ? $value['max'] : null;
        if (!isset($min) && !isset($max)) return false;
        if (isset($min) && isset($max) && $min > $max) return false;
        return true;
    }
    
    public function fieldFilterDoFilter(Query $query, IField $field, array $settings, $value, array &$sorts)
    {
        if (!isset($value['min'])
            || !strlen($value['min'])
            || !is_numeric($value['min'])
        ) {
            $field_settings = $field->getFieldSettings();
            $value['min'] = isset($field_settings['min']) ? $field_settings['min'] : 0;
        }
        if (!isset($value['max'])
            || !strlen($value['max'])
            || !is_numeric($value['max'])
        ) {
            if (!isset($field_settings)) $field_settings = $field->getFieldSettings();
            $value['max'] = isset($field_settings['max']) ? $field_settings['max'] : 100;
        }
        if ($field->getFieldType() === 'number') {
            $query->fieldIsOrGreaterThan($field, $value['min'])
                ->fieldIsOrSmallerThan($field, $value['max']);
        } else {
            $query->fieldIsOrGreaterThan($field, $value['min'], 'min')
                ->fieldIsOrSmallerThan($field, $value['max'], 'max');
        }
    }

    public function fieldFilterLabels(IField $field, array $settings, $value, $form, $defaultLabel)
    {
        $field_settings = $field->getFieldSettings();
        $prefix = isset($field_settings['prefix']) && strlen($field_settings['prefix'])
            ? $this->_application->System_TranslateString($field_settings['prefix'], $field->getFieldName() . '_field_prefix', 'entity_field')
            : '';
        $suffix = isset($field_settings['suffix']) && strlen($field_settings['suffix'])
            ? $this->_application->System_TranslateString($field_settings['suffix'], $field->getFieldName() . '_field_suffix', 'entity_field')
            : '';
        if (!isset($value['min']) || !strlen($value['min'])) {
            $value['min'] = isset($field_settings['min']) ? $field_settings['min'] : 0;
        }
        if (!isset($value['max']) || !strlen($value['max'])) {
            $value['max'] = isset($field_settings['max']) ? $field_settings['max'] : 100;
        }

        return ['' => $this->_application->H($defaultLabel . ': ' . $prefix . $value['min'] . $suffix . ' - ' . $prefix . $value['max'] . $suffix)];
    }

    public function fieldFilterConditionableInfo(IField $field)
    {
        return [
            'min' => [
                'compare' => ['<value', '>value', '<>value'],
                'tip' => __('Enter a single numeric value', 'directories'),
                'example' => 3,
                'label' => __('Minimum value', 'directories'),
            ],
            'max' => [
                'compare' => ['<value', '>value', '<>value'],
                'tip' => __('Enter a single numeric value', 'directories'),
                'example' => 10,
                'label' => __('Maximum value', 'directories'),
            ],
        ];
    }

    public function fieldFilterConditionableRule(IField $field, $filterName, array $settings, $compare, $value = null, $name = '')
    {
        $target = '[name="' . $filterName . '[' . $name . ']"]';
        switch ($compare) {
            case '<value':
            case '>value':
                return is_numeric($value) ? ['target' => $target, 'type' => $compare, 'value' => $value] : null;
            case '<>value':
                if (!strpos($value, ',')
                    || (!$value= explode(',', $value))
                    || !is_numeric($value[0])
                    || !is_numeric($value[1])
                ) return;

                return ['target' => $target, 'type' => $compare, 'value' => $value[0] . ',' . $value[1]];
            default:
        }
    }
}