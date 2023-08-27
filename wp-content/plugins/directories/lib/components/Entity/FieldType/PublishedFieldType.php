<?php
namespace SabaiApps\Directories\Component\Entity\FieldType;

use SabaiApps\Directories\Component\Field;
use SabaiApps\Directories\Component\Entity\Type\IEntity;

class PublishedFieldType extends Field\Type\AbstractType implements
    Field\Type\ISortable,
    Field\Type\ISchemable,
    Field\Type\IOpenGraph
{
    protected function _fieldTypeInfo()
    {
        return [
            'label' => __('Publish Date', 'directories'),
            'creatable' => false,
            'icon' => 'far fa-clock',
        ];
    }
    
    public function fieldSortableOptions(Field\IField $field)
    {
        return [
            ['label' => __('Newest First', 'directories')],
            ['args' => ['asc'], 'label' => __('Oldest First', 'directories')],
        ];
    }

    public function fieldSortableSort(Field\Query $query, $fieldName, array $args = null)
    {
        $query->sortByField('published', isset($args) && $args[0] === 'asc' ? 'ASC' : 'DESC')
            ->sortByField('modified', isset($args) && $args[0] === 'asc' ? 'ASC' : 'DESC'); // for non-published items
    }
    
    public function fieldSchemaProperties()
    {
        return ['datePublished'];
    }
    
    public function fieldSchemaRenderProperty(Field\IField $field, $property, IEntity $entity)
    {
        return [date('Y-m-d', $entity->getTimestamp())];
    }
    
    public function fieldOpenGraphProperties()
    {
        return ['article:published_time'];
    }
    
    public function fieldOpenGraphRenderProperty(Field\IField $field, $property, IEntity $entity)
    {
        return [date('c', $entity->getTimestamp())];
    }
}