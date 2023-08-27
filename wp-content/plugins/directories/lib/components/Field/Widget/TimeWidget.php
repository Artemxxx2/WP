<?php
namespace SabaiApps\Directories\Component\Field\Widget;

use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Field\IField;

class TimeWidget extends AbstractWidget
{
    protected function _fieldWidgetInfo()
    {
        return [
            'label' => __('Time picker', 'directories'),
            'field_types' => ['time'],
            'default_settings' => [
                'current_time_selected' => false,
                'time_format' => '',
                'time_step' => 15,
            ],
            'repeatable' => true,
        ];
    }

    public function fieldWidgetSettingsForm($fieldType, Entity\Model\Bundle $bundle, array $settings, array $parents = [], array $rootParents = [])
    {
        return [
            'current_time_selected' => [
                '#type' => 'checkbox',
                '#title' => __('Set current time selected by default', 'directories'),
                '#default_value' => !empty($settings['current_time_selected']),
            ],
            'time_format' => [
                '#type' => 'select',
                '#title' => __('Time format', 'directories'),
                '#options' => [
                    '' => __('Default', 'directories'),
                    12 => sprintf(__('%d hour', 'directories'), 12),
                    24 => sprintf(__('%d hour', 'directories'), 24),
                ],
                '#default_value' => $settings['time_format'],
            ],
            'time_step' => [
                '#type' => 'slider',
                '#title' => __('Time step interval', 'directories'),
                '#min_value' => 5,
                '#max_value' => 30,
                '#step' => 5,
                '#field_suffix' => _x('min', 'minutes', 'directories'),
                '#default_value' => $settings['time_step'],
            ],
        ];
    }

    public function fieldWidgetForm(IField $field, array $settings, $value = null, Entity\Type\IEntity $entity = null, array $parents = [], $language = null)
    {
        $field_settings = $field->getFieldSettings();
        return [
            '#type' => 'timepicker',
            '#current_time_selected' => !empty($settings['current_time_selected']),
            '#default_value' => $value,
            '#disable_day' => empty($field_settings['enable_day']),
            '#disable_end' => empty($field_settings['enable_end']),
            '#start_of_week' => $this->_application->getPlatform()->getStartOfWeek(),
            '#time_format' => isset($settings['time_format']) && in_array($settings['time_format'], [12, 24]) ? (int)$settings['time_format'] : null,
            '#time_step' => $settings['time_step'],
        ];
    }
}
