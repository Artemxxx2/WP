<?php
namespace SabaiApps\Directories\Component\Entity\FieldType;

use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Field;

class ModifiedFieldType extends Field\Type\AbstractType implements
    Field\Type\ISortable,
    Field\Type\ISchemable
{
    protected function _fieldTypeInfo()
    {
        return array(
            'label' => __('Modified Date', 'directories'),
            'creatable' => false,
            'icon' => 'far fa-clock',
        );
    }

    public function fieldSortableOptions(Field\IField $field)
    {
        return array(
            array('label' => __('Newest First', 'directories')),
            array('args' => array('asc'), 'label' => __('Oldest First', 'directories'), 'default' => true),
        );
    }

    public function fieldSortableSort(Field\Query $query, $fieldName, array $args = null)
    {
        $query->sortByField('modified', isset($args) && $args[0] === 'asc' ? 'ASC' : 'DESC');
    }

    public function fieldSchemaProperties()
    {
        return array('dateModified');
    }

    public function fieldSchemaRenderProperty(Field\IField $field, $property, Entity\Type\IEntity $entity)
    {
        return array(date('Y-m-d', $entity->getModified()));
    }
}