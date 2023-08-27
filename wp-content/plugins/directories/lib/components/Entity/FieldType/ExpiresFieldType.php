<?php
namespace SabaiApps\Directories\Component\Entity\FieldType;

use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Field;
use SabaiApps\Directories\Component\Field\Type\ConditionableDateTrait;
use SabaiApps\Directories\Component\Field\Type\QueryableDateTrait;

class ExpiresFieldType extends Field\Type\AbstractValueType implements
    Field\Type\ISortable,
    Field\Type\ISchemable,
    Field\Type\IOpenGraph,
    Field\Type\IColumnable,
    Field\Type\IQueryable,
    Field\Type\IConditionable
{
    use QueryableDateTrait, ConditionableDateTrait;

    protected function _fieldTypeInfo()
    {
        return [
            'label' => __('Exp. Date', 'directories'),
            'creatable' => false,
            'admin_only' => true,
            'icon' => 'far fa-clock',
            'schema_type' => 'date',
        ];
    }
    
    public function fieldSortableOptions(Field\IField $field)
    {
        return [
            ['label' => $label = __('Exp. Date', 'directories')],
            ['args' => ['desc'], 'label' => sprintf(__('%s (desc)', 'directories'), $label)],
        ];
    }

    public function fieldSortableSort(Field\Query $query, $fieldName, array $args = null)
    {
        $query->sortByField($fieldName, 'EMPTY_LAST', 'value'); // moves NULL or 0 to last in order
        $query->sortByField(
            $fieldName,
            isset($args) && $args[0] === 'desc' ? 'DESC' : 'ASC',
            'value'
        );
    }
    
    public function fieldSchemaProperties()
    {
        return ['expires'];
    }
    
    public function fieldSchemaRenderProperty(Field\IField $field, $property, Entity\Type\IEntity $entity)
    {
        return [date('Y-m-d', $entity->getTimestamp())];
    }
    
    public function fieldOpenGraphProperties()
    {
        return ['article:expiration_time'];
    }
    
    public function fieldOpenGraphRenderProperty(Field\IField $field, $property, Entity\Type\IEntity $entity)
    {
        return [date('c', $entity->getTimestamp())];
    }

    public function fieldColumnableInfo(Field\IField $field)
    {
        return [
            '' => [
                'label' => $field->getFieldLabel(),
                'sortby' => 'value',
                'hidden' => true,
            ],
        ];
    }

    public function fieldColumnableColumn(Field\IField $field, $value, $column = '')
    {
        if (empty($value[0])) return;

        return $this->_application->System_Date($value[0], true);
    }
}