<?php
namespace SabaiApps\Directories\Component\Field\Type;

use SabaiApps\Directories\Component\Entity\Type\IEntity;
use SabaiApps\Directories\Component\Entity\Model\Bundle;
use SabaiApps\Directories\Component\Field\IField;
use SabaiApps\Directories\Component\Field\Query;
use SabaiApps\Directories\Application;

class DateType extends AbstractType
    implements ISortable, IQueryable, IOpenGraph, IHumanReadable, ISchemable, ICopiable, IConditionable, IColumnable
{
    use QueryableDateTrait, ConditionableDateTrait;

    protected function _fieldTypeInfo()
    {
        return [
            'label' => __('Date', 'directories'),
            'default_settings' => [
                'date_range_enable' => false,
                'date_range' => null,
                'enable_time' => true,
                'month_only' => false,
            ],
            'icon' => 'far fa-calendar-alt',
        ];
    }

    public function fieldTypeSettingsForm($fieldType, Bundle $bundle, array $settings, array $parents = [], array $rootParents = [])
    {
        return [
            'month_only' => [
                '#type' => 'checkbox',
                '#title' => __('Month and year only', 'directories'),
                '#default_value' => !empty($settings['month_only']),
            ],
            'enable_time' => [
                '#type' => 'checkbox',
                '#title' => __('Enable time (hour and minute)', 'directories'),
                '#default_value' => !empty($settings['enable_time']),
                '#states' => [
                    'visible' => [
                        sprintf('input[name="%s[month_only]"]', $this->_application->Form_FieldName($parents)) => ['type' => 'checked', 'value' => false],
                    ],
                ],
            ],
            'date_range_enable' => [
                '#type' => 'checkbox',
                '#title' => __('Restrict dates', 'directories'),
                '#default_value' => !empty($settings['date_range_enable']),
                '#states' => [
                    'visible' => [
                        sprintf('input[name="%s[month_only]"]', $this->_application->Form_FieldName($parents)) => ['type' => 'checked', 'value' => false],
                    ],
                ],
            ],
            'date_range' => [
                '#type' => 'daterangepicker',
                '#default_value' => is_array($settings['date_range']) ? $settings['date_range'] : null,
                '#states' => [
                    'visible' => [
                        sprintf('input[name="%s[month_only]"]', $this->_application->Form_FieldName($parents)) => ['type' => 'checked', 'value' => false],
                        sprintf('input[name="%s[date_range_enable]"]', $this->_application->Form_FieldName($parents)) => ['type' => 'checked', 'value' => true],
                    ],
                ],
            ],
        ];
    }

    public function fieldTypeSchema()
    {
        return [
            'columns' => [
                'value' => [
                    'type' => Application::COLUMN_INTEGER,
                    'notnull' => true,
                    'unsigned' => false,
                    'default' => 0,
                    'length' => 10,
                ],
            ],
            'indexes' => [
                'value' => [
                    'fields' => ['value' => ['sorting' => 'ascending']],
                ],
            ],
        ];
    }

    public function fieldTypeOnSave(IField $field, array $values, array $currentValues = null, array &$extraArgs = [])
    {
        $ret = [];
        foreach ($values as $weight => $value) {
            if (is_null($value)) continue;

            if (!is_numeric($value)
                && (!$value = strtotime($value))
            ) {
                continue;
            } else {
                $value = intval($value);
            }
            $ret[]['value'] = $value;
        }

        return $ret;
    }

    public function fieldTypeOnLoad(IField $field, array &$values, IEntity $entity, array $allValues)
    {
        foreach ($values as $key => $value) {
            $values[$key] = $value['value'];
        }
    }

    public function fieldTypeIsModified(IField $field, $valueToSave, $currentLoadedValue)
    {
        $new = [];
        foreach ($valueToSave as $value) {
            $new[] = $value['value'];
        }
        return $currentLoadedValue !== $new;
    }

    public function fieldSortableOptions(IField $field)
    {
        return [
            [],
            ['args' => ['desc'], 'label' => __('%s (desc)', 'directories')]
        ];
    }

    public function fieldSortableSort(Query $query, $fieldName, array $args = null)
    {
        $query->sortByField($fieldName, isset($args) && $args[0] === 'desc' ? 'DESC' : 'ASC');
    }

    public function fieldOpenGraphProperties()
    {
        return ['books:release_date', 'music:release_date', 'video:release_date'];
    }

    public function fieldOpenGraphRenderProperty(IField $field, $property, IEntity $entity)
    {
        if (!$value = $entity->getSingleFieldValue($field->getFieldName())) return;

        return [date('c', $value)];
    }

    public function fieldHumanReadableText(IField $field, IEntity $entity, $separator = null, $key = null)
    {
        if (!$values = $entity->getFieldValue($field->getFieldName())) return '';

        $ret = [];
        $field_settings = $field->getFieldSettings();
        foreach ($values as $value) {
            $ret[] = !empty($field_settings['enable_time'])
                ? $this->_application->System_Date_datetime($value)
                : $this->_application->System_Date($value);
        }
        return implode(isset($separator) ? $separator : PHP_EOL, $ret);
    }

    public function fieldSchemaProperties()
    {
        return ['datePublished', 'dateModified', 'dateCreated', 'startDate', 'endDate', 'datePosted', 'validThrough'];
    }

    public function fieldSchemaRenderProperty(IField $field, $property, IEntity $entity)
    {
        if (!$value = $entity->getSingleFieldValue($field->getFieldName())) return;

        return [date('Y-m-d', $value)];
    }

    public function fieldCopyValues(IField $field, array $values, array &$allValue, $lang = null)
    {
        return $values;
    }

    public function fieldColumnableInfo(IField $field)
    {
        if (!$field->isCustomField()) return;

        return [
            '' => [
                'label' => $field->getFieldLabel(),
                'sortby' => 'value',
                'hidden' => true,
            ],
        ];
    }

    public function fieldColumnableColumn(IField $field, $value, $column = '')
    {
        foreach (array_keys($value) as $key) {
            $value[$key] = $this->_application->System_Date($value[$key], true);
        }
        return implode(', ', $value);
    }
}
