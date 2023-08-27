<?php
namespace SabaiApps\Directories\Component\Field\Renderer;

use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Field;

class DateRenderer extends Field\Renderer\AbstractRenderer
{
    protected function _fieldRendererInfo()
    {
        return [
            'field_types' => [$this->_name],
            'default_settings' => [
                'format' => 'date',
                'custom_format' => '',
                '_separator' => ', ',
            ],
            'inlineable' => true,
        ];
    }

    protected function _fieldRendererSettingsForm(Field\IField $field, array $settings, array $parents = [])
    {
        return [
            'format' => [
                '#type' => 'select',
                '#title' => __('Date/time format', 'directories'),
                '#options' => $this->_getDateTimeFormatOptions(),
                '#default_value' => $settings['format'],
            ],
            'custom_format' => [
                '#type' => 'textfield',
                '#required' => function($form) use ($parents) { return $form->getValue(array_merge($parents, ['format'])) === 'custom'; },
                '#placeholder' => 'Y-m-d',
                '#default_value' => $settings['custom_format'],
                '#description' => __('Enter the data/time format string suitable for input to PHP date() function.', 'directories'),
                '#states' => [
                    'visible' => [
                        sprintf('[name="%s[format]"]', $this->_application->Form_FieldName($parents)) => ['value' => 'custom'],
                    ],
                ],
            ],
        ];
    }

    protected function _getDateTimeFormatOptions()
    {
        return [
            'datetime' => __('Show date/time', 'directories'),
            'date' => __('Show date', 'directories'),
            'custom' => __('Custom date format', 'directories'),
        ];
    }

    protected function _fieldRendererRenderField(Field\IField $field, array &$settings, Entity\Type\IEntity $entity, array $values, $more = 0)
    {
        $ret = [];
        foreach ($values as $value) {
            $rendered = $this->_renderField($field, $settings, $entity, $value);
            if (strlen($rendered)) {
                $ret[] = $rendered;
            }
        }
        return implode($settings['_separator'], $ret);
    }

    protected function _renderField(Field\IField $field, array &$settings, Entity\Type\IEntity $entity, $value)
    {
        switch ($settings['format']) {
            case 'custom':
                return $this->_application->System_Date($value, true, $settings['custom_format']);
            case 'datetime':
                return $this->_application->System_Date_datetime($value, true);
            case 'date':
            default:
                return $this->_application->System_Date($value, true);
        }
    }

    protected function _fieldRendererReadableSettings(Field\IField $field, array $settings)
    {
        $format_options = $this->_getDateTimeFormatOptions();
        $ret = [
            'format' => [
                'label' => __('Date/time format', 'directories'),
                'value' => isset($format_options[$settings['format']]) ? $format_options[$settings['format']] : __('Show date', 'directories'),
            ],
        ];
        if ($settings['format'] === 'custom') {
            $ret['custom_format'] = [
                'label' => __('Custom date format', 'directories'),
                'value' => $settings['custom_format'],
            ];
        }

        return $ret;
    }
}
