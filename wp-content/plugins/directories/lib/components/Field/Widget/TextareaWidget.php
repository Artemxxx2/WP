<?php
namespace SabaiApps\Directories\Component\Field\Widget;

use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Field\IField;

class TextareaWidget extends AbstractWidget
{
    protected function _fieldWidgetInfo()
    {
        return [
            'label' => __('Textarea field', 'directories'),
            'field_types' => ['text', 'wp_post_content'],
            'default_settings' => [
                'rows' => 10,
                'nl2br' => false,
            ],
        ];
    }

    public function fieldWidgetSettingsForm($fieldType, Entity\Model\Bundle $bundle, array $settings, array $parents = [], array $rootParents = [])
    {
        $ret = [];
        $field_type = $fieldType instanceof IField ? $fieldType->getFieldType() : $fieldType;
        if ($field_type === 'wp_post_content') {
            // Need to add min/max length options here since these cannot be added to PostContentFieldType which will affect all post_content fields.
            $ret += [
                'min_length' => [
                    '#type' => 'number',
                    '#title' => __('Minimum length', 'directories'),
                    '#description' => __('The minimum length of value in characters.', 'directories'),
                    '#size' => 5,
                    '#integer' => true,
                    '#default_value' => isset($settings['min_length']) ? $settings['min_length'] : null,
                ],
                'max_length' => [
                    '#type' => 'number',
                    '#title' => __('Maximum length', 'directories'),
                    '#description' => __('The maximum length of value in characters.', 'directories'),
                    '#size' => 5,
                    '#integer' => true,
                    '#default_value' => isset($settings['max_length']) ? $settings['max_length'] : null,
                ],
            ];
        }
        $ret += [
            'rows' => [
                '#type' => 'slider',
                '#min_value' => 1,
                '#max_value' => 50,
                '#integer' => true,
                '#title' => __('Rows', 'directories'),
                '#default_value' => $settings['rows'],
            ],
            'nl2br' => [
                '#type' => 'checkbox',
                '#title' => __('Preserve line breaks', 'directories'),
                '#default_value' => $settings['nl2br'],
            ],
        ];

        return $ret;
    }

    public function fieldWidgetForm(IField $field, array $settings, $value = null, Entity\Type\IEntity $entity = null, array $parents = [], $language = null)
    {
        if (isset($value)) {
            $_value = is_string($value) ? $value : (is_array($value) ? $value['value'] : null);
        } else {
            $_value = null;
        }
        $field_settings = $field->getFieldSettings();
        return array(
            '#type' => 'textarea',
            '#rows' => $settings['rows'],
            '#default_value' => $_value,
            '#min_length' => isset($settings['min_length']) ? intval($settings['min_length']) : (isset($field_settings['min_length']) ? intval($field_settings['min_length']) : null),
            '#max_length' => isset($settings['max_length']) ? intval($settings['max_length']) : (isset($field_settings['max_length']) ? intval($field_settings['max_length']) : null),
            '#char_validation' => ($char_validation = isset($field_settings['char_validation']) ? $field_settings['char_validation'] : 'none'),
            '#regex' => $char_validation === 'regex' && isset($field_settings['regex']) ? $field_settings['regex'] : null,
        );
    }
    
    public function fieldWidgetFormatText(IField $field, array $settings, $value, Entity\Type\IEntity $entity)
    {
        if (!strlen($value)) {
            return '';
        }
        $value = strip_tags($value);
        if (!empty($settings['nl2br'])) {
            $value = nl2br($value);
        }
        return '<p>' . $value . '</p>';
    }
}
