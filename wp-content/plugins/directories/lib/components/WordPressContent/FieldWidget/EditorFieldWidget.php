<?php
namespace SabaiApps\Directories\Component\WordPressContent\FieldWidget;

use SabaiApps\Directories\Component\Field;
use SabaiApps\Directories\Component\Entity;

class EditorFieldWidget extends Field\Widget\AbstractWidget
{
    protected function _fieldWidgetInfo()
    {
        return [
            'label' => __('WordPress editor', 'directories'),
            'field_types' => ['text'],
            'default_settings' => [
                'rows' => get_option('default_post_edit_rows', 5),
                'no_tinymce' => false,
                'no_quicktags' => false,
                'no_media_buttons' => false,
            ],
        ];
    }

    public function fieldWidgetSettingsForm($fieldType, Entity\Model\Bundle $bundle, array $settings, array $parents = [], array $rootParents = [])
    {
        $ret = [
            'no_tinymce' => [
                '#type' => 'checkbox',
                '#title' => __('Disable Visual mode', 'directories'),
                '#default_value' => $settings['no_tinymce'],
            ],
            'no_quicktags' => [
                '#type' => 'checkbox',
                '#title' => __('Disable toolbar in Text mode', 'directories'),
                '#default_value' => $settings['no_quicktags'],
            ],
            'no_media_buttons' => [
                '#type' => 'checkbox',
                '#title' => __('Disable media buttons', 'directories'),
                '#default_value' => $settings['no_media_buttons'],
            ],
            'rows' => [
                '#type' => 'slider',
                '#min_value' => 1,
                '#max_value' => 50,
                '#integer' => true,
                '#title' => __('Rows', 'directories'),
                '#default_value' => $settings['rows'],
            ],
        ];
        if ((is_object($fieldType) && $fieldType->getFieldType() === 'wp_post_content')
            || $fieldType === 'wp_post_content'
        ) {
            $ret['admin_use_wp_editor'] = [
                '#type' => 'checkbox',
                '#title' => __('Use default WordPress editor', 'directories'),
                '#description' => __('Check this option to use the default WordPress editor when editing from the backend.', 'directories'),
                '#default_value' => !empty($settings['admin_use_wp_editor']),
            ];
        }
        return $ret;
    }

    public function fieldWidgetForm(Field\IField $field, array $settings, $value = null, Entity\Type\IEntity $entity = null, array $parents = [], $language = null)
    {
        if (!empty($settings['admin_use_wp_editor'])
            && $field->getFieldType() === 'wp_post_content'
            && is_admin()
        ) {
            add_post_type_support($field->Bundle->name, 'editor');
            return;
        }

        return [
            '#type' => 'wp_editor',
            '#default_value' => isset($value) ? (is_array($value) ? $value['value'] : $value) : null,
            '#rows' => $settings['rows'],
            '#no_tinymce' => !empty($settings['no_tinymce']),
            '#no_quicktags' => !empty($settings['no_quicktags']),
            '#no_media_buttons' => !empty($settings['no_media_buttons']),
        ] + $this->_getFieldTextSettings($field, $settings);
    }

    protected function _getFieldTextSettings(Field\IField $field, array $settings)
    {
        $field_settings = $field->getFieldSettings();
        return [
            '#min_length' => isset($field_settings['min_length']) ? intval($field_settings['min_length']) : null,
            '#max_length' => isset($field_settings['max_length']) ? intval($field_settings['max_length']) : null,
            '#char_validation' => ($char_validation = isset($field_settings['char_validation']) ? $field_settings['char_validation'] : 'none'),
            '#regex' => $char_validation === 'regex' && isset($field_settings['regex']) ? $field_settings['regex'] : null,
        ];
    }
}
