<?php
namespace SabaiApps\Directories\Component\Entity\FieldRenderer;

use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Field;

class FeaturedEndDateFieldRenderer extends Field\Renderer\DateRenderer
{
    protected function _fieldRendererInfo()
    {
        $info = parent::_fieldRendererInfo();
        $info['field_types'] = ['entity_featured'];
        $info['label'] = __('End Date', 'directories');
        return $info;
    }

    protected function _renderField(Field\IField $field, array &$settings, Entity\Type\IEntity $entity, $value)
    {
        if (empty($value['expires_at'])) return;

        return parent::_renderField($field, $settings, $entity, $value['expires_at']);
    }
}
