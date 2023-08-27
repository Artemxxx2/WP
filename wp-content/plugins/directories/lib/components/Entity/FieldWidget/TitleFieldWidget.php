<?php
namespace SabaiApps\Directories\Component\Entity\FieldWidget;

use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Field;

class TitleFieldWidget extends Field\Widget\AbstractWidget
{
    protected function _fieldWidgetInfo()
    {
        return array(
            'label' => __('Text input field', 'directories'),
            'field_types' => array($this->_name),
            'default_settings' => array(
                'minmax' => array(
                    'min' => 0,
                    'max' => 255,
                ),
                'placeholder' => null,
            ),
        );
    }

    public function fieldWidgetSettingsForm($fieldType, Entity\Model\Bundle $bundle, array $settings, array $parents = [], array $rootParents = [])
    {
        return array(
            'minmax' => array(
                '#title' => __('Min/Max characters', 'directories'),
                '#type' => 'range',
                '#integer' => true,
                '#min_value' => 0,
                '#max_value' => 255,
                '#default_value' => $settings['minmax'],
            ),
            'placeholder' => [
                '#type' => 'textfield',
                '#title' => __('Placeholder', 'directories'),
                '#default_value' => $settings['placeholder'],
            ],
        );
    }

    public function fieldWidgetForm(Field\IField $field, array $settings, $value = null, Entity\Type\IEntity $entity = null, array $parents = [], $language = null)
    {
        return [
            '#type' => 'textfield',
            '#default_value' => $value,
            '#min_length' => $settings['minmax']['min'],
            '#max_length' => $settings['minmax']['max'],
            '#placeholder' => $settings['placeholder'],
        ];
    }
}
