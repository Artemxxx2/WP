<?php
namespace SabaiApps\Directories\Component\WordPressContent\FieldWidget;

use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Field;

class PostContentFieldWidget extends EditorFieldWidget
{
    protected function _fieldWidgetInfo()
    {
        $info = parent::_fieldWidgetInfo();
        $info['field_types'] = ['wp_post_content'];
        $info['default_settings'] += [
            'min_length' => null,
            'max_length' => null,
        ];
        return $info;
    }

    public function fieldWidgetSettingsForm($fieldType, Entity\Model\Bundle $bundle, array $settings, array $parents = [], array $rootParents = [])
    {
        // Need to add min/max length options here since these cannot be added to PostContentFieldType which will affect all post_content fields.
        return [
            'min_length' => array(
                '#type' => 'number',
                '#title' => __('Minimum length', 'directories'),
                '#description' => __('The minimum length of value in characters.', 'directories'),
                '#size' => 5,
                '#integer' => true,
                '#default_value' => $settings['min_length'],
            ),
            'max_length' => array(
                '#type' => 'number',
                '#title' => __('Maximum length', 'directories'),
                '#description' => __('The maximum length of value in characters.', 'directories'),
                '#size' => 5,
                '#integer' => true,
                '#default_value' => $settings['max_length'],
            ),
        ] + parent::fieldWidgetSettingsForm($fieldType, $bundle, $settings, $parents, $rootParents);
    }

    protected function _getFieldTextSettings(Field\IField $field, array $settings)
    {
        return [
            '#min_length' => isset($settings['min_length']) ? intval($settings['min_length']) : null,
            '#max_length' => isset($settings['max_length']) ? intval($settings['max_length']) : null,
        ];
    }
}
