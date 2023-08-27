<?php
namespace SabaiApps\Directories\Component\Field\Widget;

use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Field\IField;

class PriceWidget extends AbstractWidget
{
    protected function _fieldWidgetInfo()
    {
        return [
            'label' => __('Text input field', 'directories'),
            'field_types' => ['price'],
            'default_settings' => [],
            'repeatable' => true,
        ];
    }

    public function fieldWidgetForm(IField $field, array $settings, $value = null, Entity\Type\IEntity $entity = null, array $parents = [], $language = null)
    {
        $field_settings = $field->getFieldSettings();
        $form = [
            '#type' => 'fieldset',
            '#row' => true,
            '#group' => true,
            'currency' => [
                '#type' => 'select',
                '#options' => array_combine($field_settings['currencies'], $field_settings['currencies']),
                '#default_value' => isset($value) ? $value['currency'] : null,
                '#col' => 3,
            ],
            'value' => [
                '#type' => 'number',
                '#numeric' => true,
                '#default_value' => isset($value) ? $value['value'] : null,
                '#field_prefix' => null,
                '#min_value' => isset($field_settings['min']) && is_numeric($field_settings['min']) ? $field_settings['min'] : null,
                '#max_value' => isset($field_settings['max']) && is_numeric($field_settings['max']) ? $field_settings['max'] : null,
                '#step' => 0.01,
                '#col' => 9,
            ],
        ];

        return $form;
    }
}