<?php
namespace SabaiApps\Directories\Component\Field\Renderer;

use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Field\IField;

class RangeListRenderer extends AbstractRenderer
{
    protected function _fieldRendererInfo()
    {
        return [
            'label' => __('Range list', 'directories'),
            'field_types' => ['number', 'price'],
            'default_settings' => [
                'ranges' => null,
            ],
        ];
    }

    protected function _fieldRendererSettingsForm(IField $field, array $settings, array $parents = [])
    {
        $field_settings = $field->getFieldSettings();
        return [
            'ranges' => [
                '#type' => 'rangelist',
                '#title' => __('Ranges', 'directories'),
                '#default_value' => $settings['ranges'],
                '#min_value' => isset($field_settings['min']) ? $field_settings['min'] : null,
                '#max_value' => isset($field_settings['max']) ? $field_settings['max'] : null,
                '#weight' => 1,
            ],
        ] ;
    }

    protected function _fieldRendererRenderField(IField $field, array &$settings, Entity\Type\IEntity $entity, array $values, $more = 0)
    {
        $ret = [];
        foreach ($values as $value) {
            if ($field->getFieldType() === 'price') {
                $value = $value['value'];
            }
            foreach ($settings['ranges'] as $range) {
                if ($value >= $range['min']
                    && $value <= $range['max']
                ) {
                    $ret[] = $range['label'];
                }
            }
        }
        return implode($settings['_separator'], $ret);
    }
}