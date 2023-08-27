<?php
namespace SabaiApps\Directories\Component\Field\Filter;

use SabaiApps\Directories\Component\Field\IField;
use SabaiApps\Directories\Component\Field\Query;
use SabaiApps\Directories\Component\Entity;

class DateFilter extends AbstractFilter
{
    protected function _fieldFilterInfo()
    {
        return [
            'label' => __('Date picker', 'directories'),
            'field_types' => array('date'),
            'default_settings' => [],
        ];
    }

    public function fieldFilterSettingsForm(IField $field, array $settings, array $parents = [])
    {
        return [
            'include_earlier' => [
                '#type' => 'checkbox',
                '#title' => __('Include earlier dates', 'drts'),
                '#horizontal' => true,
                '#default_value' => !empty($settings['include_earlier']),
                '#states' => [
                    'visible' => [
                        sprintf('input[name="%s"]', $this->_application->Form_FieldName(array_merge($parents, ['include_later']))) => ['type' => 'checked', 'value' => false],
                    ],
                ],
            ],
            'include_later' => [
                '#type' => 'checkbox',
                '#title' => __('Include later dates', 'drts'),
                '#horizontal' => true,
                '#default_value' => !empty($settings['include_later']),
                '#states' => [
                    'visible' => [
                        sprintf('input[name="%s"]', $this->_application->Form_FieldName(array_merge($parents, ['include_earlier']))) => ['type' => 'checked', 'value' => false],
                    ],
                ],
            ],
        ];
    }

    public function fieldFilterForm(IField $field, $filterName, array $settings, $request = null, Entity\Type\Query $query = null, array $current = null, $autoSubmit = true, array $parents = [])
    {
        $field_settings = $field->getFieldSettings();
        return [
            '#type' => 'datepicker',
            '#min_date' => !empty($field_settings['date_range_enable']) ? @$field_settings['date_range'][0] : null,
            '#max_date' => !empty($field_settings['date_range_enable']) ? @$field_settings['date_range'][1] : null,
            '#disable_time' => true,
        ];
    }

    public function fieldFilterIsFilterable(IField $field, array $settings, &$value, array $requests = null)
    {
        return !empty($value);
    }

    public function fieldFilterDoFilter(Query $query, IField $field, array $settings, $value, array &$sorts)
    {
        if (!empty($settings['include_earlier'])) {
            $query->fieldIsSmallerThan($field, $value + 86400);
        } elseif (!empty($settings['include_later'])) {
            $query->fieldIsOrGreaterThan($field, $value);
        } else {
            $query->fieldIsOrGreaterThan($field, $value)->fieldIsSmallerThan($field, $value + 86400);
        }
    }

    public function fieldFilterLabels(IField $field, array $settings, $value, $form, $defaultLabel)
    {
        $date = $this->_application->System_Date($value, true);
        if (!empty($settings['include_earlier'])) {
            $date = sprintf(__('Earlier than %s', 'drts'), $date);
        } elseif (!empty($settings['include_later'])) {
            $date = sprintf(__('Later than %s', 'drts'), $date);
        }

        return ['' => $this->_application->H($defaultLabel) . ': ' . $date];
    }
}
