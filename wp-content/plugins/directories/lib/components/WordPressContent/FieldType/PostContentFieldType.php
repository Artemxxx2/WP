<?php
namespace SabaiApps\Directories\Component\WordPressContent\FieldType;

use SabaiApps\Directories\Component\Field;
use SabaiApps\Directories\Component\Entity\Type\IEntity;

class PostContentFieldType extends Field\Type\AbstractType implements
    Field\Type\IQueryable,
    Field\Type\IOpenGraph,
    Field\Type\IHumanReadable,
    Field\Type\ISchemable,
    Field\Type\IConditionable,
    Field\Type\ITitle,
    Field\Type\ISortable
{
    use Field\Type\QueryableStringTrait, Field\Type\ConditionableStringTrait;
    
    protected function _fieldTypeInfo()
    {
        return array(
            'label' => __('Body', 'directories'),
            'entity_types' => array('post'),
            'creatable' => false,
            'disablable' => true,
            'icon' => 'fas fa-bars',
        );
    }
    
    public function fieldOpenGraphProperties()
    {
        return array('og:description');
    }
    
    public function fieldOpenGraphRenderProperty(Field\IField $field, $property, IEntity $entity)
    {
        return array($this->_application->Summarize($entity->getContent(), 300));
    }
    
    public function fieldHumanReadableText(Field\IField $field, IEntity $entity, $separator = null, $key = null)
    {
        return $this->_application->Summarize($entity->getContent(), 300);
    }
    
    public function fieldSchemaProperties()
    {
        return array('description', 'text', 'reviewBody', 'articleBody');
    }
    
    public function fieldSchemaRenderProperty(Field\IField $field, $property, IEntity $entity)
    {        
        $ret = [];
        switch ($property) {
            case 'description':
                $ret[] = $this->_application->Summarize($entity->getContent(), 300);
                break;
            case 'text':
            case 'reviewBody':
                $ret[] = $this->_application->Summarize($entity->getContent(), 0);
                break;
        }
        
        return $ret;
    }

    public function fieldTitle(Field\IField $field, array $values)
    {
        return $this->_application->Summarize($values[0], 150);
    }

    public function fieldSortableOptions(Field\IField $field)
    {
        if (!empty($field->Bundle->info['no_title'])) return false;

        return [
            [],
            ['args' => ['desc'], 'label' => sprintf(__('%s (desc)', 'directories'), $field)]
        ];
    }

    public function fieldSortableSort(Field\Query $query, $fieldName, array $args = null)
    {
        $query->sortByField('content', isset($args) && $args[0] === 'desc' ? 'DESC' : 'ASC');
    }
}