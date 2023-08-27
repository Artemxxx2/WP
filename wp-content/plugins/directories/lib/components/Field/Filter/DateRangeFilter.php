<?php
namespace SabaiApps\Directories\Component\Field\Filter;

use SabaiApps\Directories\Component\Field\IField;
use SabaiApps\Directories\Component\Field\Query;
use SabaiApps\Directories\Component\Entity;

class DateRangeFilter extends AbstractFilter
{
    protected function _fieldFilterInfo()
    {
        return [
            'label' => __('Date range picker', 'directories'),
            'field_types' => ['date'],
            'default_settings' => [
                'default_year' => null,
            ],
        ];
    }

    public function fieldFilterSettingsForm(IField $field, array $settings, array $parents = [])
    {
        $form = [
            'default_year' => [
                '#title' => __('Default year', 'directories'),
                '#type' => 'number',
                '#integer' => true,
                '#min_value' => 0,
                '#default_value' => $settings['default_year'],
            ],
        ];
        if ($other_date_fields = $this->_application->Entity_Field_options($field->getBundleName(), ['type' => 'date', 'exclude' => [$field->getFieldName()], 'empty_value' => ''])) {
            $form['end_date_field'] = [
                '#type' => 'select',
                '#title' => __('End date field', 'directories'),
                '#description' => __('Select another field to use as end date.', 'directories'),
                '#default_value' => $settings['end_date_field'],
                '#options' => $other_date_fields,
            ];
        }
        return $form;
    }

    public function fieldFilterForm(IField $field, $filterName, array $settings, $request = null, Entity\Type\Query $query = null, array $current = null, $autoSubmit = true, array $parents = [])
    {
        $field_settings = $field->getFieldSettings();
        return [
            '#type' => 'daterangepicker',
            '#calendar_months' => 1,
            '#min_date' => !empty($field_settings['date_range_enable']) ? @$field_settings['date_range'][0] : null,
            '#max_date' => !empty($field_settings['date_range_enable']) ? @$field_settings['date_range'][1] : null,
            '#default_year' => empty($settings['default_year']) ? null : (int)$settings['default_year'],
        ];
    }

    public function fieldFilterIsFilterable(IField $field, array $settings, &$value, array $requests = null)
    {
        if (empty($value[0]) && empty($value[1])) return false;

        if (!empty($value[0])) {
            $value[0] = is_numeric($value[0]) ? intval($value[0]) : strtotime($value[0]);
        }
        if (!empty($value[1])) {
            $value[1] = is_numeric($value[1]) ? intval($value[1]) : strtotime($value[1]);
        }

        return true;
    }

    public function fieldFilterDoFilter(Query $query, IField $field, array $settings, $value, array &$sorts)
    {
        if (!empty($settings['end_date_field'])
            && ($end_date_field = $this->_application->Entity_Field($field->getBundleName(), $settings['end_date_field']))
        ) {
            if (empty($value[0])) {
                $query->fieldIsOrSmallerThan($field, $value[1])
                    ->fieldIsOrGreaterThan($end_date_field, $value[1]);
            } elseif (empty($value[1])) {
                $query->fieldIsOrSmallerThan($field, $value[0])
                    ->fieldIsOrGreaterThan($end_date_field, $value[0]);
            } else {
                $query->fieldIsSmallerThan($field, $value[1] + 86400) // include until 23:59
                    ->fieldIsOrGreaterThan($end_date_field, $value[0]);
            }
        } else {
            if (!empty($value[0])) {
                $query->fieldIsOrGreaterThan($field, $value[0]);
            }
            if (!empty($value[1])) {
                $query->fieldIsOrSmallerThan($field, $value[1]);
            }
        }
    }

    public function fieldFilterLabels(IField $field, array $settings, $value, $form, $defaultLabel)
    {
        $from = !empty($value[0]) ? $this->_application->System_Date($value[0], true) : '';
        $to = !empty($value[1]) ? $this->_application->System_Date($value[1], true) : '';
        return array('' => $this->_application->H($defaultLabel) . ': ' . $from . ' - ' . $to);
    }
}
