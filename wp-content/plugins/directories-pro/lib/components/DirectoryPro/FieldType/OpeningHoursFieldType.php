<?php
namespace SabaiApps\Directories\Component\DirectoryPro\FieldType;

use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Field\Type\TimeType;

class OpeningHoursFieldType extends TimeType
{
    protected function _fieldTypeInfo()
    {
        return [
            'label' => __('Opening Hours', 'directories-pro'),
            'default_settings' => [],
            'icon' => 'far fa-clock',
            'schema_type' => 'time',
        ];
    }

    public function fieldTypeSettingsForm($fieldType, Entity\Model\Bundle $bundle, array $settings, array $parents = [], array $rootParents = [])
    {
        return [];
    }
}