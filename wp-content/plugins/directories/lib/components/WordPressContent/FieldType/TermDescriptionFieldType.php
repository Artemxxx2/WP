<?php
namespace SabaiApps\Directories\Component\WordPressContent\FieldType;

use SabaiApps\Directories\Component\Field;
use SabaiApps\Directories\Component\Entity;

class TermDescriptionFieldType extends Field\Type\AbstractType implements
    Field\Type\IQueryable,
    Field\Type\IHumanReadable,
    Field\Type\IConditionable
{
    use Field\Type\QueryableStringTrait, Field\Type\ConditionableStringTrait;
    
    protected function _fieldTypeInfo()
    {
        return array(
            'label' => __('Description', 'directories'),
            'entity_types' => array('term'),
            'creatable' => false,
            'icon' => 'fas fa-bars',
        );
    }
    
    public function fieldHumanReadableText(Field\IField $field, Entity\Type\IEntity $entity, $separator = null, $key = null)
    {
        return $this->_application->Summarize($entity->getContent(), 300);
    }
}