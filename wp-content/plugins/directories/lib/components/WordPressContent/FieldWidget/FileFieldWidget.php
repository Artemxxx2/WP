<?php
namespace SabaiApps\Directories\Component\WordPressContent\FieldWidget;

use SabaiApps\Directories\Component\Field;
use SabaiApps\Directories\Component\Entity;

class FileFieldWidget extends Field\Widget\AbstractWidget
{
    public static $txtExtensions = 'txt|asc|c|cc|h|srt';
    
    protected function _fieldWidgetInfo()
    {
        return array(
            'label' => __('File upload field', 'directories'),
            'field_types' => array('wp_file'),
            'accept_multiple' => true,
            'default_settings' => [],
        );
    }

    public function fieldWidgetForm(Field\IField $field, array $settings, $value = null, Entity\Type\IEntity $entity = null, array $parents = [], $language = null)
    {
        $settings = $field->getFieldSettings() + $settings; // compat with <1.1.x
        $type = current_user_can('upload_files') ? 'wp_media_manager' : 'wp_upload';
        if (empty($settings['allowed_files'])) {
            $wp_extensions = $settings['allowed_extensions'];
            if ($type === 'wp_upload') {
                $extensions = [];
                foreach ($settings['allowed_extensions'] as $ext) {
                    if (strpos($ext, '|')) {
                        foreach (explode('|', $ext) as $_ext) {
                            $extensions[] = $_ext;
                        }
                    } else {
                        $extensions[] = $ext;
                    }
                }
            } else {
                $extensions = $settings['allowed_extensions'];
            }
        } else {
            if ($settings['allowed_files'] === 'video') {
                $extensions = ['mp4', 'm4v', 'webm', 'ogv', 'wmv', 'flv'];
                $wp_extensions = ['mp4|m4v', 'webm', 'ogv', 'wmv', 'flv'];
            } elseif ($settings['allowed_files'] === 'audio') {
                $extensions = ['mp3', 'm4a', 'm4b', 'ogg', 'oga', 'wav', 'wma'];
                $wp_extensions = ['mp3|m4a|m4b', 'ogg|oga', 'wav', 'wma'];
            } else {
                $extensions = $wp_extensions = [];
            }
        }

        return [
            '#type' => $type,
            '#allow_only_images' => $settings['allowed_files'] === 'image',
            '#allowed_extensions' => $extensions,
            '#wp_allowed_extensions' => $wp_extensions,
            '#max_file_size' => !empty($settings['max_file_size']) ? $settings['max_file_size'] * 1024 : null,
            '#multiple' => $field->getFieldMaxNumItems() !== 1,
            '#default_value' => $value,
            '#max_num_files' => $field->getFieldMaxNumItems(),
            '#sortable' => true,
        ];
    }
}