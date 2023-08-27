<?php
namespace SabaiApps\Directories\Component\Entity\FieldWidget;

use SabaiApps\Directories\Component\Field\Widget\AbstractWidget;
use SabaiApps\Directories\Component\Field\IField;
use SabaiApps\Directories\Component\Entity\Type\IEntity;

abstract class AbstractMetaFieldWidget extends AbstractWidget
{
    protected function _fieldWidgetInfo()
    {
        return [
            'accept_multiple' => false,
            'repeatable' => false,
        ];
    }

    public function fieldWidgetForm(IField $field, array $settings, $value = null, IEntity $entity = null, array $parents = [], $language = null)
    {
        // TODO: Implement fieldWidgetForm() method.
    }
}