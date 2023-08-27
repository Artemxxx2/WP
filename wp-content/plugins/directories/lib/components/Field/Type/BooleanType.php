<?php
namespace SabaiApps\Directories\Component\Field\Type;

use SabaiApps\Directories\Component\Field\IField;
use SabaiApps\Directories\Component\Field\Query;
use SabaiApps\Directories\Component\Entity\Type\IEntity;
use SabaiApps\Directories\Component\Entity\Model\Bundle;
use SabaiApps\Directories\Application;

class BooleanType extends AbstractValueType
    implements ISchemable, IQueryable, IHumanReadable, IConditionable, ISortable
{
    protected function _fieldTypeInfo()
    {
        return array(
            'label' => __('ON/OFF', 'directories'),
            'default_widget' => 'checkbox',
            'default_settings' => [],
            'icon' => 'fas fa-toggle-on',
        );
    }

    public function fieldTypeSchema()
    {
        return array(
            'columns' => array(
                'value' => array(
                    'type' => Application::COLUMN_BOOLEAN,
                    'unsigned' => true,
                    'notnull' => true,
                    'was' => 'value',
                    'default' => false,
                ),
            ),
            'indexes' => array(
                'value' => array(
                    'fields' => array('value' => array('sorting' => 'ascending')),
                    'was' => 'value',
                ),
            ),
        );
    }

    public function fieldTypeOnSave(IField $field, array $values, array $currentValues = null, array &$extraArgs = [])
    {
        $ret = [];
        foreach ($values as $weight => $value) {
            $ret[][$this->_valueColumn] = is_array($value) ? (bool)$value[$this->_valueColumn] : (bool)$value;
        }

        return $ret;
    }
    
    public function fieldSchemaProperties()
    {
        return array('acceptsReservations');
    }
    
    public function fieldSchemaRenderProperty(IField $field, $property, IEntity $entity)
    {
        $value = $entity->getSingleFieldValue($field->getFieldName());
        if (null === $value) return;
        
        return (bool)$value ? 'True' : 'False';
    }
    
    public function fieldQueryableInfo(IField $field, $inAdmin = false)
    {
        return array(
            'example' => __('1 or 0', 'directories'),
            'tip' => __('Enter 1 for true (checked), 0 for false (unchecked).', 'directories'),
        );
    }
    
    public function fieldQueryableQuery(Query $query, $fieldName, $paramStr, Bundle $bundle)
    {
        if ((bool)$paramStr) {
            $query->fieldIs($fieldName, true);
        } else {
            $query->startCriteriaGroup('OR')
                ->fieldIs($fieldName, false)
                ->fieldIsNull($fieldName)
                ->finishCriteriaGroup();
        }
    }
    
    public function fieldHumanReadableText(IField $field, IEntity $entity, $separator = null, $key = null)
    {
        return (bool)$entity->getSingleFieldValue($field->getFieldName()) === true ? __('Yes', 'directories') : __('No', 'directories');
    }
    
    public function fieldConditionableInfo(IField $field, $isServerSide = false)
    {
        return [
            '' => [
                'compare' => ['empty', 'filled'],
            ],
        ];
    }
    
    public function fieldConditionableRule(IField $field, $compare, $value = null, $name = '')
    {
        switch ($compare) {
            case 'empty':
                return ['type' => 'checked', 'value' => false];
            case 'filled':
                return ['type' => 'unchecked', 'value' => false];
            default:
                return;
        }
    }

    public function fieldConditionableMatch(IField $field, array $rule, array $values, IEntity $entity)
    {
        switch ($rule['type']) {
            case 'checked':
            case 'unchecked':
                if (isset($values[0])) {
                    $value = is_array($values[0]) ? $values[0][$this->_valueColumn] : $values[0];
                } else {
                    $value = null;
                }
                return $rule['type'] === 'unchecked' ? empty($value) === $rule['value'] : !empty($value) === $rule['value'];
            default:
                return false;
        }
    }

    public function fieldSortableOptions(IField $field)
    {
        return [
            [],
        ];
    }

    public function fieldSortableSort(Query $query, $fieldName, array $args = null)
    {
        $query->sortByField($fieldName, 'DESC');
    }
}
