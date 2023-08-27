<?php
namespace SabaiApps\Directories\Component\Entity\FieldRenderer;

use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Field;

class IdFieldRenderer extends Field\Renderer\AbstractRenderer
{    
    protected function _fieldRendererInfo()
    {
        return [
            'field_types' => [$this->_name],
            'default_settings' => [],
            'inlineable' => true,
        ];
    }

    protected function _fieldRendererRenderField(Field\IField $field, array &$settings, Entity\Type\IEntity $entity, array $values, $more = 0)
    {
        return $entity->getId();
    }
}