<?php
namespace SabaiApps\Directories\Component\Entity\FieldType;

use SabaiApps\Directories\Component\Field;
use SabaiApps\Directories\Component\Entity\Type\IEntity;

abstract class AbstractMetaFieldType extends Field\Type\AbstractType
{
    protected function _fieldTypeInfo()
    {
        return [
            'creatable' => false,
            'admin_only' => true,
            'icon' => 'fas fa-cog',
            'load_empty' => true,
        ];
    }

    public function fieldTypeOnSave(Field\IField $field, array $values, array $currentValues = null, array &$extraArgs = [])
    {
        $extraArgs['entity_meta'][$field->getFieldName()] = $values[0];
    }

    public function fieldTypeOnLoad(Field\IField $field, array &$values, IEntity $entity, array $allValues)
    {
        if ($this->_application->getPlatform()->hasEntityMeta($entity->getType(), $entity->getId(), $field->getFieldName())) {
            $values = [$this->_application->getPlatform()->getEntityMeta($entity->getType(), $entity->getId(), $field->getFieldName())];
        }
    }
}