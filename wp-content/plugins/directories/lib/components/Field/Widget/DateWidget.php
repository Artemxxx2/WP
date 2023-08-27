<?php
namespace SabaiApps\Directories\Component\Field\Widget;

use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Field\IField;

class DateWidget extends AbstractWidget
{
    protected function _fieldWidgetInfo()
    {
        return [
            'label' => __('Date picker', 'directories'),
            'field_types' => [$this->_name],
            'default_settings' => [
                'current_date_selected' => false,
                'default_year' => null,
                'time_format' => 24,
            ],
            'repeatable' => true,
        ];
    }

    public function fieldWidgetSettingsForm($fieldType, Entity\Model\Bundle $bundle, array $settings, array $parents = [], array $rootParents = [])
    {
        return [
            'current_date_selected' => [
                '#type' => 'checkbox',
                '#title' => __('Set current date selected by default', 'directories'),
                '#default_value' => !empty($settings['current_date_selected']),
            ],
            'default_year' => [
                '#title' => __('Default year', 'directories'),
                '#type' => 'number',
                '#integer' => true,
                '#min_value' => 0,
                '#default_value' => $settings['default_year'],
                '#states' => [
                    'visible' => [
                        sprintf('[name="%s[current_date_selected]"]', $this->_application->Form_FieldName($parents)) => ['type' => 'checked', 'value' => false],
                    ],
                ],
            ],
            'time_format' => [
                '#type' => 'select',
                '#title' => __('Time format', 'directories'),
                '#options' => [
                    12 => sprintf(__('%d hour', 'directories'), 12),
                    24 => sprintf(__('%d hour', 'directories'), 24),
                ],
                '#default_value' => $settings['time_format'],
                '#states' => [
                    'visible' => [
                        sprintf('[name="%s[settings][month_only]"]', $this->_application->Form_FieldName($rootParents)) => ['type' => 'checked', 'value' => false],
                        sprintf('[name="%s[settings][enable_time]"]', $this->_application->Form_FieldName($rootParents)) => ['type' => 'checked', 'value' => true],
                    ],
                ],
            ],
        ];
    }

    public function fieldWidgetForm(IField $field, array $settings, $value = null, Entity\Type\IEntity $entity = null, array $parents = [], $language = null)
    {
        $field_settings = $field->getFieldSettings();
        return [
            '#type' => empty($field_settings['month_only']) ? 'datepicker' : 'monthpicker',
            '#current_date_selected' => !empty($settings['current_date_selected']),
            '#min_date' => !empty($field_settings['date_range_enable']) ? @$field_settings['date_range'][0] : null,
            '#max_date' => !empty($field_settings['date_range_enable']) ? @$field_settings['date_range'][1] : null,
            '#disable_time' => empty($field_settings['enable_time']),
            '#time_12hr' => isset($settings['time_format']) && $settings['time_format'] == 12,
            '#default_value' => $value,
            '#default_year' => $settings['default_year'],
        ];
    }
}
