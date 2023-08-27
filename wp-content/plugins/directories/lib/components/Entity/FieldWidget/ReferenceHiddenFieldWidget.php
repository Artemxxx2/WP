<?php
namespace SabaiApps\Directories\Component\Entity\FieldWidget;

use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Field;

class ReferenceHiddenFieldWidget extends Field\Widget\AbstractWidget
{
    protected function _fieldWidgetInfo()
    {
        return [
            'label' => __('Hidden field', 'directories'),
            'field_types' => ['entity_reference'],
            'accept_multiple' => false,
        ];
    }

    public function fieldWidgetForm(Field\IField $field, array $settings, $value = null, Entity\Type\IEntity $entity = null, array $parents = [], $language = null)
    {
        // Show nothing
    }
}
