<?php
namespace SabaiApps\Directories\Component\Field\Renderer;

use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Field\IField;

class NameRenderer extends AbstractRenderer
{
    protected function _fieldRendererInfo()
    {
        return [
            'field_types' => [$this->_name],
            'default_settings' => [
                'name_custom_format' => false,
                'name_format' => null,
                '_separator' => ', ',
            ],
        ];
    }

    protected function _fieldRendererSettingsForm(IField $field, array $settings, array $parents = [])
    {
        return $this->_application->Field_Type('name')->formatNameSettingsForm($settings, $parents);
    }

    protected function _fieldRendererRenderField(IField $field, array &$settings, Entity\Type\IEntity $entity, array $values, $more = 0)
    {
        foreach (array_keys($values) as $key) {
            $values[$key] = $this->_application->H($this->_application->Field_Type('name')->formatName($values[$key], $settings));
        }
        return implode($settings['_separator'], $values);
    }
}
