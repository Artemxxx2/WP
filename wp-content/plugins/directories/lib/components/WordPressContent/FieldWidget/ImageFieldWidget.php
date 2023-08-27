<?php
namespace SabaiApps\Directories\Component\WordPressContent\FieldWidget;

use SabaiApps\Directories\Component\Field;
use SabaiApps\Directories\Component\Entity;

class ImageFieldWidget extends Field\Widget\AbstractWidget
{
    protected function _fieldWidgetInfo()
    {
        return [
            'label' => __('Image upload field', 'directories'),
            'field_types' => ['wp_image'],
            'accept_multiple' => true,
            'default_settings' => [
                'non_admin_disable_ajax' => false,
            ],
        ];
    }

    public function fieldWidgetSettingsForm($fieldType, Entity\Model\Bundle $bundle, array $settings, array $parents = [], array $rootParents = [])
    {
        return [
            'non_admin_disable_ajax' => [
                '#type' => 'checkbox',
                '#title' => __('Disable AJAX upload for non-admin users', 'directories'),
                '#default_value' => !empty($settings['non_admin_disable_ajax']),
                '#horizontal' => true,
            ],
        ];
    }

    public function fieldWidgetForm(Field\IField $field, array $settings, $value = null, Entity\Type\IEntity $entity = null, array $parents = [], $language = null)
    {
        $field_settings = $field->getFieldSettings();
        $form = [
            '#type' => current_user_can('upload_files') ? 'wp_media_manager' : 'wp_upload',
            '#max_file_size' => !empty($field_settings['max_file_size']) ? $field_settings['max_file_size'] * 1024 : null,
            '#multiple' => $field->getFieldMaxNumItems() !== 1,
            '#allow_only_images' => true,
            '#default_value' => $value,
            '#max_num_files' => $field->getFieldMaxNumItems(),
            '#sortable' => true,
        ];
        if (!empty($settings['non_admin_disable_ajax'])
            && !$this->_application->getUser()->isAdministrator()
        ) {
            $form['#type'] = 'wp_upload';
            $form['#ajax_upload'] = false;
            $form['#upload_dir'] = $this->_application->getComponent('System')->getTmpDir();
        }
        return $form;
    }
}