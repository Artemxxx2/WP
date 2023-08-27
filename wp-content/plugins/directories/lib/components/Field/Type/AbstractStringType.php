<?php
namespace SabaiApps\Directories\Component\Field\Type;

use SabaiApps\Directories\Component\Entity\Type\IEntity;
use SabaiApps\Directories\Component\Entity\Model\Bundle;
use SabaiApps\Directories\Component\Field\IField;
use SabaiApps\Directories\Component\Field\Query;
use SabaiApps\Directories\Application;

abstract class AbstractStringType extends AbstractValueType implements
    ISortable,
    ISchemable,
    IQueryable,
    IOpenGraph,
    IHumanReadable,
    IConditionable,
    IPersonalData,
    IColumnable,
    ITitle
{
    use QueryableStringTrait, ConditionableStringTrait;

    public function fieldTypeSettingsForm($fieldType, Bundle $bundle, array $settings, array $parents = [], array $rootParents = [])
    {
        return array(
            'min_length' => array(
                '#type' => 'number',
                '#title' => __('Minimum length', 'directories'),
                '#description' => __('The minimum length of value in characters.', 'directories'),
                '#size' => 5,
                '#integer' => true,
                '#default_value' => isset($settings['min_length']) ? $settings['min_length'] : null,
            ),
            'max_length' => array(
                '#type' => 'number',
                '#title' => __('Maximum length', 'directories'),
                '#description' => __('The maximum length of value in characters.', 'directories'),
                '#size' => 5,
                '#integer' => true,
                '#default_value' => isset($settings['max_length']) ? $settings['max_length'] : null,
            ),
            'char_validation' => [
                '#type' => 'select',
                '#title' => __('Character validation', 'directories'),
                '#options' => $this->_application->Filter('field_type_string_char_validations', [
                    'integer' => __('Allow only integer numbers', 'directories'),
                    'alpha' => __('Allow only alphabetic characters', 'directories'),
                    'alnum' => __('Allow only alphanumeric characters', 'directories'),
                    'lower' => __('Allow only lowercase characters', 'directories'),
                    'upper' => __('Allow only uppercase characters', 'directories'),
                    'url' => __('Must be a valid URL', 'directories'),
                    'email' => __('Must be a valid e-mail address', 'directories'),
                    'regex' => __('Must match a regular expression', 'directories'),
                    'none' => __('No validation', 'directories'),
                ], [$fieldType, $bundle]),
                '#default_value' => isset($settings['char_validation']) ? $settings['char_validation'] : 'none',
            ],
            'regex' => array(
                '#type' => 'textfield',
                '#title' => __('Regular Expression', 'directories'),
                '#description' => __('Example: /^[0-9a-z]+$/i', 'directories'),
                '#default_value' => isset($settings['regex']) ? $settings['regex'] : null,
                '#states' => array(
                    'visible' => array(
                        sprintf('select[name="%s[char_validation]"]', $this->_application->Form_FieldName($parents)) => array('value' => 'regex'),
                    ),
                ),
                '#required' => array(array($this, 'isRegexRequired'), array($parents)),
                '#display_unrequired' => true,
                '#size' => 20,
            ),
        );
    }

    public function isRegexRequired($form, $parents)
    {
        $values = $form->getValue($parents);
        return @$values['char_validation'] === 'regex';
    }

    public function fieldTypeSchema()
    {
        return array(
            'columns' => array(
                'value' => array(
                    'type' => Application::COLUMN_VARCHAR,
                    'length' => 255,
                    'notnull' => true,
                    'was' => 'value',
                    'default' => '',
                ),
            ),
            'indexes' => array(
                'value' => array(
                    'fields' => array('value' => array('sorting' => 'ascending', 'length' => 191)),
                    'was' => 'value',
                ),
            ),
        );
    }

    public function fieldTypeDefaultValueForm($fieldType, Bundle $bundle, array $settings, array $parents = [])
    {
        return [
            '#type' => 'textfield',
        ];
    }

    public function fieldSortableOptions(IField $field)
    {
        return array(
            [],
            array('args' => array('desc'), 'label' => sprintf(__('%s (desc)', 'directories'), $field))
        );
    }

    public function fieldSortableSort(Query $query, $fieldName, array $args = null)
    {
        $query->sortByField($fieldName, isset($args) && $args[0] === 'desc' ? 'DESC' : 'ASC');
    }

    public function fieldSchemaProperties()
    {
        return ['name', 'alternateName', 'title', 'hiringOrganization', 'articleSection', 'servesCuisine', 'sku'];
    }

    public function fieldSchemaRenderProperty(IField $field, $property, IEntity $entity)
    {
        if ($property === 'hiringOrganization') {
            if (!$value = $entity->getSingleFieldValue($field->getFieldName())) return;

            return [[
                '@type' => 'Organization',
                'name' => $value,
            ]];
        }
        return $entity->getFieldValue($field->getFieldName());
    }

    public function fieldOpenGraphProperties()
    {
        return array('books:isbn', 'music:isrc', 'product:isbn');
    }

    public function fieldOpenGraphRenderProperty(IField $field, $property, IEntity $entity)
    {
        if (!$value = $entity->getSingleFieldValue($field->getFieldName())) return;

        return array($value);
    }

    public function fieldHumanReadableText(IField $field, IEntity $entity, $separator = null, $key = null)
    {
        if (!$values = $entity->getFieldValue($field->getFieldName())) return '';

        return implode(isset($separator) ? $separator : ', ', $values);
    }
    
    public function fieldPersonalDataExport(IField $field, IEntity $entity)
    {
        return ($value = $entity->getFieldValue($field->getFieldName())) ? implode(', ', $value) : null;
    }

    public function fieldPersonalDataErase(IField $field, IEntity $entity)
    {
        if (!$field->isFieldRequired()
            || (!$value = $entity->getSingleFieldValue($field->getFieldName()))
        ) return true; // delete

        return $this->_application->getPlatform()->anonymizeText($value); // anonymize
    }

    public function fieldColumnableInfo(IField $field)
    {
        if (!$field->isCustomField()) return;

        return [
            '' => [
                'label' => $field->getFieldLabel(),
                'sortby' => $field->getFieldMaxNumItems() === 1 ? $this->_valueColumn : null,
                'hidden' => true,
            ],
        ];
    }

    public function fieldColumnableColumn(IField $field, $value, $column = '')
    {
        return $this->_application->H(implode(', ', $value));
    }

    public function fieldTitle(IField $field, array $values)
    {
        return isset($values[0][$this->_valueColumn]) ? $values[0][$this->_valueColumn] : null;
    }

    protected function _onSaveValue(IField $field, $value, array $settings)
    {
        if (null !== $value = parent::_onSaveValue($field, $value, $settings)) {
            if (!$schema_type = $this->fieldTypeInfo('schema_type')) $schema_type = $field->getFieldType();
            $value = $this->_application->getPlatform()->encodeString($value, $schema_type, $this->_valueColumn);
        }
        return $value;
    }
}
