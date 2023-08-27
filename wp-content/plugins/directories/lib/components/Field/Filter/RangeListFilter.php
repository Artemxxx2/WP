<?php
namespace SabaiApps\Directories\Component\Field\Filter;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Component\Field\IField;
use SabaiApps\Directories\Component\Field\Query;

class RangeListFilter extends AbstractOptionFilter
{
    protected $_minMaxSeparator = ',';

    public function __construct(Application $application, $name)
    {
        parent::__construct($application, $name);
        $this->_label = __('Range list', 'directories');
        $this->_fieldTypes = ['number', 'price'];
        $this->_defaultSettings = ['ranges' => null];
    }

    public function fieldFilterSettingsForm(IField $field, array $settings, array $parents = [])
    {
        $field_settings = $field->getFieldSettings();
        return [
            'ranges' => [
                '#type' => 'rangelist',
                '#title' => __('Ranges', 'directories'),
                '#default_value' => $settings['ranges'],
                '#min_value' => isset($field_settings['min']) ? $field_settings['min'] : null,
                '#max_value' => isset($field_settings['max']) ? $field_settings['max'] : null,
                '#weight' => 1,
            ],
        ] + parent::fieldFilterSettingsForm($field, $settings, $parents);
    }

    protected function _getOptions(IField $field, array $settings, &$noEscape = false)
    {
        $options = [];
        foreach ($settings['ranges'] as $range) {
            $key = $range['min'] . $this->_minMaxSeparator . $range['max'];
            $options[$key] = $range['label'];
        }
        return $options;
    }

    protected function _getFacetOptions(IField $field, array $settings)
    {
        $ranges = [];
        foreach ($settings['ranges'] as $range) {
            $key = $range['min'] . $this->_minMaxSeparator  . $range['max'];
            $ranges[$key] = $range;
        }
        return [
            'facet_type' => 'range',
            'column' => $this->_valueColumn,
            'ranges' => $ranges,
        ];
    }

    public function fieldFilterDoFilter(Query $query, IField $field, array $settings, $value, array &$sorts)
    {
        if (!$ranges = $this->_getRangeValues($field, $value)) return;

        if ($settings['type'] !== 'checkboxes'
            || count($ranges) === 1
        ) {
            $range = array_shift($ranges);
            $query->fieldIsOrGreaterThan($field, $range['min'])
                ->fieldIsOrSmallerThan($field, $range['max']);
            return;
        }

        if ($settings['andor'] === 'OR') {
            $query->startCriteriaGroup('OR');
            foreach ($ranges as $range) {
                $query->startCriteriaGroup()
                    ->fieldIsOrGreaterThan($field, $range['min'])
                    ->fieldIsOrSmallerThan($field, $range['max'])
                    ->finishCriteriaGroup();
            }
            $query->finishCriteriaGroup();
        } else {
            foreach ($ranges as $range) {
                $query->fieldIsOrGreaterThan($field, $range['min'])
                    ->fieldIsOrSmallerThan($field, $range['max']);
            }
        }
    }

    protected function _getRangeValues(IField $field, $value)
    {
        $field_settings = $field->getFieldSettings();
        $ret = [];
        foreach ((array)$value as $_value) {
            $_value = explode($this->_minMaxSeparator, $_value);

            if (!isset($_value[0])
                || !strlen($_value[0] = trim($_value[0]))
                || !is_numeric($_value[0])
            ) {
                $_value[0] = isset($field_settings['min']) ? $field_settings['min'] : 0;
            }
            if (!isset($_value[1])
                || !strlen($_value[1] = trim($_value[1]))
                || !is_numeric($_value[1])
            ) {
                $_value[1] = isset($field_settings['max']) ? $field_settings['max'] : 100;
            }

            $ret[] = ['min' => $_value[0], 'max' => $_value[1]];
        }

        return $ret;
    }
}
