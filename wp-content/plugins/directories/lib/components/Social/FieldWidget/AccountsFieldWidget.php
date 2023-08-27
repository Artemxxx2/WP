<?php
namespace SabaiApps\Directories\Component\Social\FieldWidget;

use SabaiApps\Directories\Component\Field;
use SabaiApps\Directories\Component\Entity;

class AccountsFieldWidget extends Field\Widget\AbstractWidget
{   
    protected function _fieldWidgetInfo()
    {
        return [
            'label' => __('Social Accounts', 'directories'),
            'field_types' => ['social_accounts'],
            'default_settings' => [
                'cols' => 2,
            ],
            'disable_edit_max_num_items' => true,
            'max_num_items' => 0,
        ];
    }
    
    public function fieldWidgetSettingsForm($fieldType, Entity\Model\Bundle $bundle, array $settings, array $parents = array(), array $rootParents = array())
    {
        return [
            'cols' => [
                '#type' => 'slider',
                '#title' => __('Number of columns', 'directories'),
                '#default_value' => $settings['cols'],
                '#horizontal' => true,
                '#min_value' => 1,
                '#max_value' => 4,
            ],
        ];
    }

    public function fieldWidgetForm(Field\IField $field, array $settings, $value = null, Entity\Type\IEntity $entity = null, array $parents = [], $language = null)
    {
        $field_settings = $field->getFieldSettings();
        if (empty($field_settings['medias'])) return;
        
        $form = ['#type' => 'fieldset', '#row' => true, '#row_gutter' => 'md'];
        $col = 12 / $settings['cols'];
        $medias = $this->_application->Social_Medias();
        foreach ($field_settings['medias'] as $media_name) {
            if (!isset($medias[$media_name])) continue;
            
            $media = $medias[$media_name];
            $form[$media_name] = array(
                '#type' => isset($media['type']) ? $media['type'] : 'url',
                '#allow_url_no_protocol' => true,
                '#field_prefix' => isset($media['icon']) ? sprintf('<i class="fa-fw %s"></i>', $media['icon']) : null,
                '#default_value' => isset($value[$media_name]) ? $value[$media_name] : null,
                '#regex' => isset($media['regex']) ? $media['regex'] : null,
                '#placeholder' => isset($media['placeholder']) ? $media['placeholder'] : sprintf(_x('%s URL', 'social media URL', 'directories'), $media['label']),
                '#col' => ['md' => $col],
                '#class' => 'drts-social-media-field ' . DRTS_BS_PREFIX . 'mb-2',
                '#data' => ['social-media-name' => $media_name],
            );
        }
        return $form;
    }
}