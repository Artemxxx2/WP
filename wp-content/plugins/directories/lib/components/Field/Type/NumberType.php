<?php
namespace SabaiApps\Directories\Component\Field\Type;

use SabaiApps\Directories\Component\Entity\Type\IEntity;
use SabaiApps\Directories\Component\Entity\Model\Bundle;
use SabaiApps\Directories\Component\Field\IField;
use SabaiApps\Directories\Component\Field\Query;
use SabaiApps\Directories\Application;

class NumberType extends AbstractValueType implements
    ISortable,
    IQueryable,
    IOpenGraph,
    IHumanReadable,
    IConditionable,
    IColumnable
{
    use ConditionableNumberTrait, QueryableNumberTrait;

    protected function _fieldTypeInfo()
    {
        return array(
            'label' => __('Number', 'directories'),
            'default_widget' => 'textfield',
            'default_settings' => array(
                'min' => null,
                'max' => null,
                'decimals' => 0,
                'prefix' => null,
                'suffix' => null,
            ),
            'icon' => 'fas fa-hashtag',
        );
    }

    public function fieldTypeSettingsForm($fieldType, Bundle $bundle, array $settings, array $parents = [], array $rootParents = [])
    {
        return array(
            '#element_validate' => [
                [[$this, 'validateMinMaxSettings'], ['decimals']],
            ],
            'min' => array(
                '#type' => 'number',
                '#title' => __('Minimum', 'directories'),
                '#description' => __('The minimum value allowed in this field.', 'directories'),
                '#size' => 10,
                '#default_value' => $settings['min'],
                '#numeric' => true,
                '#step' => 0.01,
            ),
            'max' => array(
                '#type' => 'number',
                '#title' => __('Maximum', 'directories'),
                '#description' => __('The maximum value allowed in this field.', 'directories'),
                '#size' => 10,
                '#default_value' => $settings['max'],
                '#numeric' => true,
                '#step' => 0.01,
            ),
            'decimals' => array(
                '#type' => 'select',
                '#title' => __('Decimals', 'directories'),
                '#description' => __('The number of digits to the right of the decimal point.', 'directories'),
                '#options' => array(0 => __('0 (no decimals)', 'directories'), 1 => 1, 2 => 2),
                '#default_value' => $settings['decimals'],
            ),
            'prefix' => array(
                '#type' => 'textfield',
                '#title' => __('Field prefix', 'directories'),
                '#description' => __('Example: $, #, -', 'directories'),
                '#size' => 20,
                '#default_value' => $settings['prefix'],
                '#no_trim' => true,
            ),
            'suffix' => array(
                '#type' => 'textfield',
                '#title' => __('Field suffix', 'directories'),
                '#description' => __('Example: km, %, g', 'directories'),
                '#size' => 20,
                '#default_value' => $settings['suffix'],
                '#no_trim' => true,
            ),
        );
    }

    public function fieldTypeSchema()
    {
        return array(
            'columns' => array(
                'value' => array(
                    'type' => Application::COLUMN_DECIMAL,
                    'notnull' => true,
                    'length' => 18,
                    'scale' => 2,
                    'unsigned' => false,
                    'was' => 'value',
                    'default' => 0,
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

    public function fieldTypeDefaultValueForm($fieldType, Bundle $bundle, array $settings, array $parents = [])
    {
        return [
            '#type' => 'number',
            '#numeric' => true,
        ];
    }

    protected function _onSaveValue(IField $field, $value, array $settings)
    {
        return is_numeric($value) ? round($value, $settings['decimals']) : null;
    }

    public function fieldTypeOnLoad(IField $field, array &$values, IEntity $entity, array $allValues)
    {
        foreach ($values as $key => $value) {
            $values[$key] = (float)$value[$this->_valueColumn];
        }
    }
    
    public function fieldSortableOptions(IField $field)
    {
        return array(
            [],
            array('args' => array('asc'), 'label' => __('%s (asc)', 'directories'))
        );
    }
    
    public function fieldSortableSort(Query $query, $fieldName, array $args = null)
    {
        $query->sortByField($fieldName, isset($args) && $args[0] === 'asc' ? 'ASC' : 'DESC');
    }
    
    public function fieldOpenGraphProperties()
    {
        return array('books:page_count', 'music:duration', 'video:duration');
    }
    
    public function fieldOpenGraphRenderProperty(IField $field, $property, IEntity $entity)
    {
        if (!$value = (int)$entity->getSingleFieldValue($field->getFieldName())) return;
        
        switch ($property) {
            case 'music:duration':
            case 'video:duration':
                return $value * 60; // we assume the value is in minutes here, may need a filter to change that
            default:
                return $value;
        }
    }
    
    public function fieldHumanReadableText(IField $field, IEntity $entity, $separator = null, $key = null)
    {
        if (!$values = $this->_getFormattedValues($field, $entity)) return '';
        
        return implode(isset($separator) ? $separator : ', ', $values);
    }

    protected function _getFormattedValues(IField $field, $values)
    {
        if ($values instanceof IEntity) {
            if (!$values = $values->getFieldValue($field->getFieldName())) return;
        }

        $settings = $field->getFieldSettings();
        $decimals = empty($settings['decimals']) ? 0 : $settings['decimals'];
        $prefix = isset($settings['prefix']) ? $settings['prefix'] : '';
        $suffix = isset($settings['suffix']) ? $settings['suffix'] : '';
        foreach (array_keys($values) as $i) {
            $values[$i] = $prefix . $this->_application->getPlatform()->numberFormat($values[$i], $decimals) . $suffix;
        }
        return $values;
    }

    public function fieldColumnableInfo(IField $field)
    {
        if (!$field->isCustomField()) return;

        return [
            '' => [
                'label' => $field->getFieldLabel(),
                'sortby' => $this->_valueColumn,
                'hidden' => true,
            ],
        ];
    }

    public function fieldColumnableColumn(IField $field, $value, $column = '')
    {
        if (!$values = $this->_getFormattedValues($field, $value)) return '';

        return implode(', ', $values);
    }
}