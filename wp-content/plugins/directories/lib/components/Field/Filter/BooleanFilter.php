<?php
namespace SabaiApps\Directories\Component\Field\Filter;

use SabaiApps\Directories\Component\Field\IField;
use SabaiApps\Directories\Component\Field\Query;
use SabaiApps\Directories\Component\Entity;

class BooleanFilter extends AbstractFilter implements IConditionable
{
    protected $_filterColumn = 'value', $_trueValue = true, $_nullOnly = false;
    
    protected function _fieldFilterInfo()
    {
        return [
            'label' => __('Single checkbox', 'directories'),
            'field_types' => ['boolean', 'switch'],
            'default_settings' => [
                'checkbox_label' => null,
                'hide_empty' => false,
                'hide_count' => false,
                'inverse' => false,
            ],
            'facetable' => true,
        ];
    }

    public function fieldFilterSettingsForm(IField $field, array $settings, array $parents = [])
    {
        $form = [
            'checkbox_label' => [
                '#type' => 'textfield',
                '#title' => __('Checkbox label', 'directories'),
                '#description' => __('Enter the label displayed next to the checkbox.', 'directories'),
                '#default_value' => $settings['checkbox_label'],
                '#required' => true,
                '#weight' => 1,
            ],
            'hide_empty' => [
                '#type' => 'checkbox',
                '#title' => __('Hide empty', 'directories'),
                '#default_value' => !empty($settings['hide_empty']),
                '#weight' => 5,
            ],
            'inverse' => [
                '#type' => 'checkbox',
                '#title' => __('Inverse results', 'directories'),
                '#default_value' => !empty($settings['inverse']),
                '#weight' => 7,
            ],
        ];

        if ($this->_application->getComponent('View')->getConfig('filters', 'facet_count')) {
            $form['hide_count'] = [
                '#type' => 'checkbox',
                '#title' => __('Hide count', 'directories'),
                '#default_value' => $settings['hide_count'],
                '#weight' => 6,
            ];
        }
        return $form;
    }
    
    public function fieldFilterForm(IField $field, $filterName, array $settings, $request = null, Entity\Type\Query $query = null, array $current = null, $autoSubmit = true, array $parents = [])
    {
        if (false === $facets = $this->_getFacets($field, $settings, $query)) return;

        if (!isset($current)) {
            $current = [
                '#type' => 'checkbox',
                '#on_value' => 1,
                '#off_value' => '',
                '#on_label' => $this->_getCheckboxLabel($field, $settings),
                '#switch' => false,
                '#entity_filter_form_type' => 'checkboxes',
            ];
        }

        if (isset($facets)) {
            $count = empty($facets) ? 0 : (is_array($facets) ? array_sum($facets) : (int)$facets);
            $current['#option_no_escape'] = true;
            $current['#on_label'] = $this->_application->H($current['#on_label']) . ' <span>(' . $count . ')</span>';
            $current['#disabled'] = $count === 0;
        }

        return $current;
    }

    protected function _getFacets(IField $field, array $settings, Entity\Type\Query $query = null)
    {
        if (!$query->view_enable_facet_count
            || !empty($settings['hide_count'])
        ) return;

        $options = [];
        if ($property = $field->isPropertyField()) {
            $options[empty($settings['inverse']) ? 'filters_not' : 'filters'] = [$property => 0];
        } else {
            $options['column'] = $this->_filterColumn;
            if (!$this->_nullOnly
                && isset($this->_filterColumn)
            ) {
                $options[empty($settings['inverse']) ? 'filters' : 'filters_not'] = [$this->_filterColumn => $this->_trueValue];
            }
        }

        $facets = $this->_application->Entity_Facets($field, $query->getFieldQuery(), $options);

        if (!$facets) {
            return empty($settings['hide_empty']) ? [] : false;
        }

        return $facets;
    }

    protected function _getCheckboxLabel(IField $field, array $settings)
    {
        return sprintf($settings['checkbox_label'], $field->getFieldLabel(true));
    }
    
    public function fieldFilterIsFilterable(IField $field, array $settings, &$value, array $requests = null)
    {
        return isset($value[0]) && is_numeric($value[0]);
    }
    
    public function fieldFilterDoFilter(Query $query, IField $field, array $settings, $value, array &$sorts)
    {
        $value = (bool)$value;
        if (!empty($settings['inverse'])) {
            $value = !$value;
        }

        if (!$value) {
            if ($this->_nullOnly) {
                if ($field->isPropertyField()) {
                    $query->fieldIs($field,  0);
                } else {
                    $query->fieldIsNull($field, $this->_filterColumn);
                }
            } else {
                $query->startCriteriaGroup('OR')
                    ->fieldIsNull($field, $this->_filterColumn)
                    ->fieldIsNot($field, $this->_trueValue, $this->_filterColumn)
                    ->finishCriteriaGroup();
            }
        } else {
            if ($this->_nullOnly) {
                if ($field->isPropertyField()) {
                    $query->fieldIsNot($field, 0);
                } else {
                    $query->fieldIsNotNull($field, $this->_filterColumn);
                }
            } else {
                $query->fieldIs($field, $this->_trueValue, $this->_filterColumn);
            }
        }
    }
    
    public function fieldFilterLabels(IField $field, array $settings, $value, $form, $defaultLabel)
    {
        return [$value => $this->_application->H($this->_getCheckboxLabel($field, $settings))];
    }

    public function fieldFilterConditionableInfo(IField $field)
    {
        return [
            '' => [
                'compare' => ['empty', 'filled'],
            ],
        ];
    }

    public function fieldFilterConditionableRule(IField $field, $filterName, array $settings, $compare, $value = null, $name = '')
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
}