<?php
namespace SabaiApps\Directories\Component\Field\Widget;

use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Field\IField;

class SelectWidget extends AbstractWidget
{
    protected function _fieldWidgetInfo()
    {
        return array(
            'label' => __('Select list', 'directories'),
            'field_types' => array('choice'),
            'accept_multiple' => true,
            'default_settings' => [
                'sort' => false,
            ],
        );
    }

    public function fieldWidgetSettingsForm($fieldType, Entity\Model\Bundle $bundle, array $settings, array $parents = [], array $rootParents = [])
    {
        return [
            'sort' => [
                '#title' => __('Sort by label', 'directories'),
                '#type' => 'checkbox',
                '#default_value' => !empty($settings['sort']),
            ],
        ];
    }

    public function fieldWidgetForm(IField $field, array $settings, $value = null, Entity\Type\IEntity $entity = null, array $parents = [], $language = null)
    {
        $options = $this->_application->Field_ChoiceOptions($field, !empty($settings['sort']), $language);
        $is_multiple = $field->getFieldMaxNumItems() !== 1;
        if (isset($value)) {
            $default_value = [];
            foreach ($value as $_value) {
                $default_value[] = $_value;
            }
        } else {
            $default_value = $options['default']; 
        }
        if (!empty($default_value)) {
            if (!$is_multiple) {
                $default_value = array_shift($default_value);
            }
        } else {
            $default_value = null; 
        }

        return array(
            '#type' => 'select',
            '#options' => $is_multiple ? $options['options'] : array('' => __('— Select —', 'directories')) + $options['options'],
            '#multiple' => $is_multiple,
            '#max_selection' => $field->getFieldMaxNumItems(),
            '#default_value' => $default_value,
            '#empty_value' => '',
        );
    }
}