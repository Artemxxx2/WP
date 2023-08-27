<?php
namespace SabaiApps\Directories\Component\Field\Widget;

use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Field\IField;

class IconWidget extends AbstractWidget
{    
    protected function _fieldWidgetInfo()
    {
        return array(
            'label' => __('Icon picker', 'directories'),
            'field_types' => array($this->_name),
            'default_settings' => [
                'rows' => 6,
                'cols' => 6,
            ],
        );
    }

    public function fieldWidgetSettingsForm($fieldType, Entity\Model\Bundle $bundle, array $settings, array $parents = [], array $rootParents = [])
    {
        return [
            'cols' => [
                '#type' => 'select',
                '#title' => __('Number of columns', 'directories'),
                '#default_value' => $settings['cols'],
                '#options' => [2 => 2, 3 => 3, 4 => 4, 6 => 6, 12 => 12],
                '#horizontal' => true,
            ],
            'rows' => [
                '#type' => 'slider',
                '#title' => __('Number of rows', 'directories'),
                '#default_value' => $settings['rows'],
                '#min_value' => 1,
                '#max_value' => 20,
                '#horizontal' => true,
            ],
        ];
    }

    public function fieldWidgetForm(IField $field, array $settings, $value = null, Entity\Type\IEntity $entity = null, array $parents = [], $language = null)
    {
        return array(
            '#type' => 'iconpicker',
            '#default_value' => isset($value) ? $value : null,
            '#rows' => $settings['rows'],
            '#cols' => $settings['cols'],
        );
    }
}