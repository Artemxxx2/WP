<?php
namespace SabaiApps\Directories\Component\Field\Type;

use SabaiApps\Directories\Component\Field\IField;
use SabaiApps\Directories\Component\Entity\Type\IEntity;
use SabaiApps\Directories\Component\Entity\Model\Bundle;

class StringType extends AbstractStringType implements ILabellable
{
    protected function _fieldTypeInfo()
    {
        return [
            'label' => __('Single Line Text', 'directories'),
            'default_widget' => 'textfield',
            'default_settings' => [
                'min_length' => null,
                'max_length' => null,
                'char_validation' => 'none',
                'regex' => null,
                'prefix' => null,
                'suffix' => null,
            ],
            'icon' => 'fas fa-minus',
        ];
    }

    public function fieldTypeSettingsForm($fieldType, Bundle $bundle, array $settings, array $parents = [], array $rootParents = [])
    {
        return parent::fieldTypeSettingsForm($fieldType, $bundle, $settings, $parents, $rootParents) + [
            'prefix' => [
                '#type' => 'textfield',
                '#title' => __('Field prefix', 'directories'),
                '#description' => __('Example: $, #, -', 'directories'),
                '#size' => 20,
                '#default_value' => $settings['prefix'],
                '#no_trim' => true,
            ],
            'suffix' => [
                '#type' => 'textfield',
                '#title' => __('Field suffix', 'directories'),
                '#description' => __('Example: km, %, g', 'directories'),
                '#size' => 20,
                '#default_value' => $settings['suffix'],
                '#no_trim' => true,
            ],
        ];
    }

    public function fieldLabellableLabels(IField $field, IEntity $entity)
    {
        return $entity->getFieldValue($field->getFieldName());
    }
}
