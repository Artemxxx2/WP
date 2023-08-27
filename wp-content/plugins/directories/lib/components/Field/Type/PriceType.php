<?php
namespace SabaiApps\Directories\Component\Field\Type;

use SabaiApps\Directories\Component\Entity\Type\IEntity;
use SabaiApps\Directories\Component\Entity\Model\Bundle;
use SabaiApps\Directories\Component\Field\IField;
use SabaiApps\Directories\Component\Field\Query;
use SabaiApps\Directories\Application;
use SabaiApps\Directories\Component\Field\Renderer\PriceRenderer;

class PriceType extends AbstractType implements
    ISortable,
    IQueryable,
    IHumanReadable,
    IConditionable,
    ISchemable
{
    use ConditionableNumberTrait, QueryableNumberTrait;

    protected $_valueColumn = 'value';

    protected function _fieldTypeInfo()
    {
        return [
            'label' => __('Price', 'directories'),
            'default_settings' => [
                'min' => null,
                'max' => null,
                'currencies' => ['USD'],
            ],
            'icon' => 'fas fa-money-bill-alt',
        ];
    }

    public function fieldTypeSettingsForm($fieldType, Bundle $bundle, array $settings, array $parents = [], array $rootParents = [])
    {
        return [
            '#element_validate' => [[$this, 'validateMinMaxSettings']],
            'min' => [
                '#type' => 'number',
                '#title' => __('Minimum', 'directories'),
                '#description' => __('The minimum value allowed in this field.', 'directories'),
                '#size' => 10,
                '#default_value' => $settings['min'],
                '#numeric' => true,
                '#step' => 0.01,
            ],
            'max' => [
                '#type' => 'number',
                '#title' => __('Maximum', 'directories'),
                '#description' => __('The maximum value allowed in this field.', 'directories'),
                '#size' => 10,
                '#default_value' => $settings['max'],
                '#numeric' => true,
                '#step' => 0.01,
            ],
            'currencies' => [
                '#type' => 'sortablecheckboxes',
                '#title' => __('Currencies', 'directories'),
                '#options' => $this->_application->System_Currency_options(),
                '#default_value' => $settings['currencies'],
                '#columns' => 3,
            ],
        ];
    }

    public function fieldTypeSchema()
    {
        return [
            'columns' => [
                'value' => [
                    'type' => Application::COLUMN_DECIMAL,
                    'notnull' => true,
                    'length' => 18,
                    'scale' => 3,
                    'unsigned' => false,
                    'was' => 'value',
                    'default' => 0,
                ],
                'currency' => [
                    'type' => Application::COLUMN_VARCHAR,
                    'length' => 3,
                    'notnull' => true,
                    'was' => 'currency',
                    'default' => '',
                ],
            ],
            'indexes' => [
                'value' => [
                    'fields' => ['value' => ['sorting' => 'ascending']],
                    'was' => 'value',
                ],
                'currency' => [
                    'fields' => ['currency' => ['sorting' => 'ascending']],
                    'was' => 'currency',
                ],
            ],
        ];
    }

    public function fieldTypeOnSave(IField $field, array $values, array $currentValues = null, array &$extraArgs = [])
    {
        $settings = (array)$field->getFieldSettings();
        $default_currency = empty($settings['currencies']) || !is_array($settings['currencies']) ? 'USD' : $settings['currencies'][0];
        $ret = [];
        foreach ($values as $value) {
            if (!is_array($value)
                || empty($value)
                || !isset($value[$this->_valueColumn])
                || !is_numeric($value[$this->_valueColumn])
            ) continue;

            if (empty($value['currency'])) {
                $value['currency'] = $default_currency;
            } else {
                if (!in_array($value['currency'], (array)$settings['currencies'])) continue;
            }

            $ret[] = [
                'currency' => $value['currency'],
                $this->_valueColumn => $value[$this->_valueColumn],
            ];
        }

        return $ret;
    }

    public function fieldTypeIsModified(IField $field, $valueToSave, $currentLoadedValue)
    {
        if (count($currentLoadedValue) !== count($valueToSave)) return true;

        foreach (array_keys($currentLoadedValue) as $key) {
            if (count($currentLoadedValue[$key]) !== count($valueToSave[$key])
                || array_diff_assoc($currentLoadedValue[$key], $valueToSave[$key])
            ) return true;
        }
        return false;
    }

    public function fieldSortableOptions(IField $field)
    {
        return [
            [],
            ['args' => ['asc'], 'label' => __('%s (asc)', 'directories')],
        ];
    }

    public function fieldSortableSort(Query $query, $fieldName, array $args = null)
    {
        $query->sortByField($fieldName, isset($args) && $args[0] === 'asc' ? 'ASC' : 'DESC');
    }

    public function fieldSchemaProperties()
    {
        return ['priceRange', 'price', 'priceCurrency', 'baseSalary'];
    }

    public function fieldSchemaRenderProperty(IField $field, $property, IEntity $entity)
    {
        if (!$value = $entity->getSingleFieldValue($field->getFieldName())) return;

        switch ($property) {
            case 'priceRange':
                return $this->_getFormattedValues($field, $entity, [$value]);
            case 'price':
                return $value['value'];
            case 'priceCurrency':
                return $value['currency'];
            case 'baseSalary':
                return [[
                    '@type' => 'MonetaryAmount',
                    'value' => $value['value'],
                    'currency' => $value['currency'],
                ]];
        }
    }

    public function fieldHumanReadableText(IField $field, IEntity $entity, $separator = null, $key = null)
    {
        if (!$values = $this->_getFormattedValues($field, $entity)) return '';

        return implode(isset($separator) ? $separator : ', ', $values);
    }

    protected function _getFormattedValues(IField $field, IEntity $entity, array $values = null)
    {
        if (!isset($values)
            && (!$values = $entity->getFieldValue($field->getFieldName()))
        ) return;

        foreach (array_keys($values) as $i) {
            $values[$i] = $this->_application->System_Currency_format($values[$i]['value'], $values[$i]['currency']);
        }

        return $values;
    }
}