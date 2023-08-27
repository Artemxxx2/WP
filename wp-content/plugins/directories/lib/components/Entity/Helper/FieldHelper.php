<?php
namespace SabaiApps\Directories\Component\Entity\Helper;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Exception;
use SabaiApps\Directories\Component\Entity\Type\IEntity;
use SabaiApps\Directories\Component\Entity\Model\Bundle;
use SabaiApps\Directories\Component\Field\Type\IConditionable;
use SabaiApps\Directories\Component\Form;

class FieldHelper
{
    private $_fields = [];

    /**
     * Returns a field object of an entity
     */
    public function help(Application $application, $entityOrBundle, $fieldName = null, $componentName = null, $group = '', $throwException = false)
    {
        if ($entityOrBundle instanceof IEntity) {
            $bundle_name = $entityOrBundle->getBundleName();
        } elseif ($entityOrBundle instanceof Bundle) {
            $bundle_name = $entityOrBundle->name;
        } elseif (!is_string($entityOrBundle)) {
            if (!$throwException) return false;
            throw new Exception\InvalidArgumentException();
        } else {
            if (isset($componentName)) {
                if (!$bundle = $application->Entity_Bundle($entityOrBundle, $componentName, $group, $throwException)) {
                    return false;
                }
                $bundle_name = $bundle->name;
            } else {
                if (!$application->Entity_Bundle($entityOrBundle)) {
                    if (!$throwException) return false;
                    throw new Exception\InvalidArgumentException();
                }
                $bundle_name = $entityOrBundle;
            }
        }
        // Check if fields are already loaded
        if (!isset($this->_fields[$bundle_name])) {
            // Load fields
            $this->_fields[$bundle_name] = [];
            foreach ($application->Entity_Bundle($bundle_name)->with('Fields', 'FieldConfig')->Fields as $field) {
                $this->_fields[$bundle_name][$field->getFieldName()] = $field;
            }
        }
        if (isset($fieldName)) {
            return isset($this->_fields[$bundle_name][$fieldName]) ? $this->_fields[$bundle_name][$fieldName] : null;
        }

        return $this->_fields[$bundle_name];
    }

    protected function _getEntity(Application $application, $entity)
    {
        if (!$entity instanceof IEntity) {
            if (is_array($entity)) {
                $entity_id = $entity[0];
                $entity_type = $entity[1];
            } else {
                $entity_id = $entity;
                $entity_type = 'post';
            }
            if (!$entity = $application->Entity_Entity($entity_type, $entity_id)) {
                return;
            }
        }
        return $entity;
    }

    public function options(Application $application, $entityOrBundle, array $options = [])
    {
        $options += [
            'exclude' => null,
            'type' => null,
            'type_exclude' => null,
            'interface' => null,
            'interface_exclude' => null,
            'empty_value' => null,
            'prefix' => '',
            'name_prefix' => '',
            'exclude_disabled' => false,
            'return_disabled' => false,
            'exclude_property' => false,
        ];
        $fields = $disabled = [];
        if (isset($options['type'])) settype($options['type'], 'array');
        if (isset($options['type_exclude'])) settype($options['type_exclude'], 'array');
        if (isset($options['interface'])) $options['interface'] = '\SabaiApps\Directories\Component\\' . $options['interface'];
        if (isset($options['interface_exclude'])) $options['interface_exclude'] = '\SabaiApps\Directories\Component\\' . $options['interface_exclude'];
        foreach ($this->help($application, $entityOrBundle) as $field_name => $field) {
            if (!empty($options['exclude'])
                && in_array($field_name, $options['exclude'])
            ) continue;

            if (!empty($options['type'])
                && !in_array($field->getFieldType(), $options['type'])
            ) continue;

            if (!empty($options['type_exclude'])
                && in_array($field->getFieldType(), $options['type_exclude'])
            ) continue;

            if (!empty($options['exclude_property'])
                && $field->isPropertyField()
            ) continue;

            if (!$field_type = $application->Field_Type($field->getFieldType(), true)) continue;

            if (isset($options['interface'])
                && !$field_type instanceof $options['interface']
            ) continue;

            if (isset($options['interface_exclude'])
                && $field_type instanceof $options['interface_exclude']
            ) continue;

            if ($field->getFieldData('disabled')
                && !empty($options['exclude_disabled'])
            ) continue;

            $key = (string)$options['name_prefix'] . $field_name;
            $label = $field_type->fieldTypeInfo('admin_only') ? $field_type->fieldTypeInfo('label') : $field->getFieldLabel();
            $label = (string)$options['prefix'] . $label . ' (' . $field_name . ')';
            $fields[$key] = $label;
            if ($field->getFieldData('disabled')
                && !empty($options['return_disabled'])
            ) {
                $disabled[$key] = $label;
            }
        }
        if (!empty($fields)) {
            asort($fields, SORT_STRING);
            if (isset($options['empty_value'])) {
                $fields = array($options['empty_value'] => __('— Select —', 'directories')) + $fields;
            }
        }

        return empty($options['return_disabled']) ? $fields : [$fields, $disabled];
    }

    public function render(Application $application, $entity, $fieldName, $rendererName, array $settings, array $values = null, $index = null)
    {
        return $this->renderBySettingsReference($application, $entity, $fieldName, $rendererName, $settings, $values, $index);
    }

    public function renderBySettingsReference(Application $application, $entity, $fieldName, $rendererName, array &$settings, array $values = null, $index = null)
    {
        if (!$entity = $this->_getEntity($application, $entity)) {
            $application->logError('Invalid entity ' . $entity);
            return '';
        }
        if (!$field = $this->help($application, $entity, $fieldName)) {
            $application->logError('Invalid field ' . $fieldName);
            return '';
        }
        if (!isset($values)
            && (!$values = $entity->getFieldValue($field->getFieldName()))
        ) {
            return '';
        }

        if (isset($index)) {
            if (!array_key_exists($index, $values)) return '';

            $values = [$values[$index]];
        }
        try {
            $renderer = $application->Field_Renderers_impl($rendererName);
            if ($default_settings = $renderer->fieldRendererInfo('default_settings')) {
                $settings += $default_settings;
            }
            $html = $renderer->fieldRendererRenderField($field, $settings, $entity, $values);
            if (!is_array($html) && (is_null($html) || !strlen($html))) return '';
        } catch (Exception\IException $e) {
            $application->logError($e);
            return '';
        }

        return $application->Filter('entity_field_render', $html, [$entity, $field, $rendererName, $settings, $values]);
    }

    public function load(Application $application, $entityType, array $entities = null, $force = false, $cache = true, array $fields = null)
    {
        if ($entityType instanceof IEntity) {
            if ($entityType->isFieldsLoaded() && !$force) {
                return;
            }
            $entities = array($entityType->getId() => $entityType);
            $entityType = $entityType->getType();
        } elseif (empty($entities)) {
            return;
        }
        if (!$force) {
            $entities_loaded = $application->Entity_Field_loadCache($entities);
            foreach (array_keys($entities) as $entity_key) {
                $entity = $entities[$entity_key];
                if (isset($entities_loaded[$entity->getId()])) {
                    unset($entities[$entity_key]);
                }
            }
        }
        if (!empty($entities)) {
            $this->_loadEntityFields($application, $entityType, $entities, $cache, $fields);
            if ($cache && !isset($fields)) {
                try {
                    $application->Entity_Field_saveCache($entities);
                } catch (\Exception $e) {
                    $application->logError($e);
                }
            }
        }
    }

    protected function _loadEntityFields(Application $application, $entityType, array $entities, $cache, array $fields = null)
    {
        $entities_by_bundle = $field_values_by_bundle = $field_types_by_bundle = $fields_by_bundle = [];
        foreach (array_keys($entities) as $entity_key) {
            $entities_by_bundle[$entities[$entity_key]->getBundleName()][$entities[$entity_key]->getId()] = $entities[$entity_key];
        }
        $bundles = $application->Entity_Bundles(array_keys($entities_by_bundle));
        foreach (array_keys($bundles) as $bundle_name) {
            $fields_by_bundle[$bundle_name] = $field_types_by_bundle[$bundle_name] = [];
            foreach ($application->Entity_Field($bundle_name) as $field) {
                $field_name = $field->getFieldName();
                if (isset($fields)
                    && !in_array($field_name, $fields)
                ) continue;

                $fields_by_bundle[$bundle_name][$field_name] = $field;
                $field_types_by_bundle[$bundle_name][$field_name] = $field->getFieldType();
            }
            if (empty($fields_by_bundle[$bundle_name])) continue;

            $field_values_by_bundle[$bundle_name] = $application->Entity_Storage()
                ->fetchValues($entityType, array_keys($entities_by_bundle[$bundle_name]), array_keys($fields_by_bundle[$bundle_name]));
        }

        // Load field values
        foreach (array_keys($bundles) as $bundle_name) {
            foreach ($entities_by_bundle[$bundle_name] as $entity_id => $entity) {
                $entity_field_values = $entity_field_priority = [];
                foreach ($application->Entity_Field($bundle_name) as $field) {
                    if ($field->isPropertyField()
                        && (!$field->FieldConfig || !$field->FieldConfig->schema_type)
                    ) continue; // do not call fieldTypeOnLoad() on property fields

                    if (!$ifield_type = $application->Field_Type($field->getFieldType(), true)) continue;

                    // Check whether or not the value for this field is cacheable
                    if ($cache && false === $ifield_type->fieldTypeInfo('cacheable')) continue;

                    $field_name = $field->getFieldName();
                    if (!isset($field_values_by_bundle[$bundle_name][$entity_id][$field_name])) {
                        $values = $ifield_type->fieldTypeInfo('load_empty') ? [] : null;
                    } else {
                        $values = $field_values_by_bundle[$bundle_name][$entity_id][$field_name];
                    }
                    $entity_field_values[$field_name] = $values;
                    $priority = (int)$ifield_type->fieldTypeInfo('on_load_priority');
                    $entity_field_priority[$priority][] = $field_name;
                }
                ksort($entity_field_priority);

                foreach (array_keys($entity_field_priority) as $priority) {
                    foreach ($entity_field_priority[$priority] as $field_name) {
                        $field = $application->Entity_Field($bundle_name, $field_name);
                        if (($conditions = $field->getFieldConditions())
                            && !$application->Entity_Field_checkConditions($conditions, $entity, $entity->getProperties() + $entity_field_values)
                        ) {
                            $entity_field_values[$field_name] = false; // always hide
                        } else {
                            if (null !== $entity_field_values[$field_name]) {
                                // Let the field type component for each field to work on values on load
                                $application->Field_Type($field->getFieldType())->fieldTypeOnLoad($field, $entity_field_values[$field_name], $entity, $entity_field_values);
                            }
                        }
                    }
                }

                // Init entity and let other components take action
                $entity->initFields($entity_field_values, $field_types_by_bundle[$bundle_name], !isset($fields));

                // Let other components modify entity
                $application->Action('entity_field_values_loaded', [$entity, $bundles[$bundle_name], $cache, $fields]);
            }
        }
    }

    public function checkConditions(Application $application, array $conditions, IEntity $entity, array $values = null)
    {
        if ((isset($conditions['add']) && !$conditions['add'])
            || empty($conditions['rules'])
        ) return true;

        if (!isset($values)) {
            $values = $entity->getFieldValues(true);
        }

        foreach ($conditions['rules'] as $rule) {
            if (strpos($rule['field'], ',')) {
                if (!$_rule = explode(',', $rule['field'])) continue;

                $field_name = $_rule[0];
                $_name = $_rule[1];
            } else {
                $field_name = $rule['field'];
                $_name = '';
            }

            if ((!$_field = $application->Entity_Field($entity, $field_name))
                || (!$field_type = $application->Field_Type($_field->getFieldType(), true))
                || !$field_type instanceof IConditionable
                || !$field_type->fieldConditionableInfo($_field, true)
                || (!$_rule = $field_type->fieldConditionableRule($_field, $rule['compare'], $rule['value'], $_name))
                || (!$_rule = $application->Filter('entity_field_condition_rule', ['name' => $_name] + $_rule, [$_field, $rule['compare'], $rule['value'], $_name, 'php']))
            ) continue;

            if (isset($values[$field_name])) {
                if ($_field->isPropertyField()) {
                    $field_values = [$values[$field_name]];
                } else {
                    $field_values = is_array($values[$field_name]) ? $values[$field_name] : [];
                }
            } else {
                $field_values = [];
            }
            if ($field_type->fieldConditionableMatch($_field, $_rule, $field_values, $entity)) {
                // Matched
                if ($conditions['action']['match'] === 'any') {
                    return $conditions['action']['name'] === 'hide' ? false : true;
                }
            } else {
                // Not matched
                if ($conditions['action']['match'] === 'all') {
                    return $conditions['action']['name'] === 'hide' ? true : false;
                }
            }
        }

        if ($conditions['action']['match'] === 'any') {
            // None matched
            return $conditions['action']['name'] === 'hide' ? true : false;
        } else {
            // All matched
            return $conditions['action']['name'] === 'hide' ? false : true;
        }
    }

    public function loadCache(Application $application, array $entities)
    {
        $platform = $application->getPlatform();
        $loaded = [];
        foreach (array_keys($entities) as $entity_key) {
            $entity = $entities[$entity_key];
            if ($cache = $platform->getCache('_entity_field_' . $entity->getBundleName() . '__' . $entity->getId(), 'entity_field')) {
                if (!is_array($cache[0])
                    || !is_array($cache[1])
                ) {
                    $application->logError('Invalid field cache values for entity (ID: ' . $entity->getId() . ' Type: ' . $entity->getType() . ').');
                    continue;
                }
                $entity->initFields($cache[0], $cache[1]);
                $loaded[$entity->getId()] = $entity->getId();
            }
        }
        return $loaded;
    }

    public function saveCache(Application $application, array $entities)
    {
        $platform = $application->getPlatform();
        foreach (array_keys($entities) as $entity_key) {
            $entity = $entities[$entity_key];
            $platform->setCache(
                array($entity->getFieldValues(), $entity->getFieldTypes()),
                '_entity_field_' . $entity->getBundleName() . '__' . $entity->getId(),
                null,
                'entity_field'
            );
        }
    }

    public function removeCache(Application $application, $bundleName, array $entityIds)
    {
        $platform = $application->getPlatform();
        foreach ($entityIds as $entity_id) {
            $platform->deleteCache('_entity_field_' . $bundleName . '__' . $entity_id, 'entity_field');
        }
    }

    public function cleanCache(Application $application, $bundleName = null)
    {
        $application->getPlatform()->clearCache('entity_field');
    }
    
    public function conditionSettingsForm(Application $application, $bundleName, array $settings = [], array $parents = [], $isServerSide = false, array $excludeFields = [], array $submitValues = [])
    {
        $conditionable_fields = $application->Entity_Field($bundleName);
        if (!empty($excludeFields)) {
            foreach ($excludeFields as $field_name) {
                unset($conditionable_fields[$field_name]);
            }
        }
        $conditions = [];
        foreach (array_keys($conditionable_fields) as $field_name) {
            $_field = $conditionable_fields[$field_name];
            if ((!$field_type = $application->Field_Type($_field->getFieldType(), true))
                || !$field_type instanceof IConditionable
                || (!$condition_info = $field_type->fieldConditionableInfo($_field, $isServerSide))
            ) continue;

            foreach (array_keys($condition_info) as $name) {
                $option_name = strlen($name) ? $field_name . ',' . $name : $field_name;
                $conditions[$option_name] = $condition_info[$name];
                if (!isset($conditions[$option_name]['label'])) {
                    $conditions[$option_name]['label'] = $field_type->fieldTypeInfo('admin_only') ? $field_type->fieldTypeInfo('label') : $_field->getFieldLabel();
                }
                $conditions[$option_name]['label'] .= ' - ' . $field_name;
            }
        }
        if (empty($conditions)) return [];
        
        $form = [
            '#type' => 'fieldset',
            '#tree' => true,
            '#tree_allow_override' => false,
            '#element_validate' => [function(Form\Form $form, &$value, $element) {
                if (empty($value['rules'])
                    || (!$value['rules'] = array_filter($value['rules']))
                ) {
                    $value = null;
                }
            }],
            'add' => [
                '#type' => 'checkbox',
                '#title' => __('Add conditional rules', 'directories'),
                '#default_value' => !empty($settings['add']),
                '#horizontal' => true,
            ],
            'action' => [
                '#horizontal' => true,
                '#row' => true,
                '#title' => ' ',
                'name' => [
                    '#type' => 'select',
                    '#options' => [
                        'show' => __('Show', 'conditional rule', 'directories'),
                        'hide' => __('Hide', 'conditional rule', 'directories'),
                    ],
                    '#default_value' => isset($settings['action']['name']) ? $settings['action']['name'] : 'show',
                    '#weight' => 1,
                    '#col' => 3,
                ],
                'match' => [
                    '#type' => 'select',
                    '#options' => [
                        'all' => __('if all of the following match', 'directories'),
                        'any' => __('if any of the following matches', 'directories'),
                    ],
                    '#default_value' => isset($settings['action']['match']) ? $settings['action']['match'] : 'all',
                    '#weight' => 2,
                    '#col' => 9,
                ],
                '#states' => [
                    'visible' => [
                        sprintf('input[name="%s"]', $application->Form_FieldName(array_merge($parents, ['add']))) => ['type' => 'checked', 'value' => true],
                    ],
                ],
            ],
            'rules' => [
                '#title' => ' ',
                '#horizontal' => true,
                '#states' => [
                    'visible' => [
                        sprintf('input[name="%s"]', $application->Form_FieldName(array_merge($parents, ['add']))) => ['type' => 'checked', 'value' => true],
                    ],
                ],
            ],
        ];
        if (isset($submitValues['rules'])) {
            // coming from form submission
            // need to check request values since fields may have been added/removed
            $rules = empty($submitValues['rules']) ? [null] : $submitValues['rules'];
        } else {
            if (!empty($settings['rules'])) {
                $rules = $settings['rules'];
            } else {
                $rules = [];
            }
            $rules[] = null; // for adding a new rule
        }
        foreach ($rules as $i => $rule) {
            $form['rules'][$i] = array(
                '#type' => 'field_condition',
                '#conditions' => $conditions,
                '#default_value' => $rule,
            );
        }
        $form['rules']['_add'] = [
            '#type' => 'addmore',
            '#next_index' => isset($i) ? ++$i : 1,
        ];
        return $form;
    }

    public function schemaType(Application $application, $fieldName = null)
    {
        $field_map = $this->_getFieldSchema($application, 'field_map');
        return !isset($fieldName) ? $field_map : (isset($field_map[$fieldName]) ? $field_map[$fieldName] : null);
    }

    public function columnType(Application $application, $schemaType, $column = null)
    {
        $columns = $this->_getFieldSchema($application, 'columns');
        return isset($column) ? $columns[$schemaType][$column] : (isset($columns[$schemaType]) ? $columns[$schemaType] : null);
    }

    protected function _getFieldSchema(Application $application, $key)
    {
        if (!$ret = $application->getPlatform()->getCache('entity_field_schema')) {
            $ret = array('columns' => [], 'field_map' => []);
            foreach ($application->getModel('FieldConfig', 'Entity')->fetch() as $field_config) {
                if (!$field_config->schema_type
                    || (!$field_type = $application->Field_Type($field_config->schema_type, true))
                ) continue;

                // Add field name to schema type map
                $ret['field_map'][$field_config->name] = $field_config->schema_type;

                if (isset($ret['columns'][$field_config->schema_type])
                    || (!$field_schema = $field_type->fieldTypeSchema())
                    || !is_array($field_schema)
                ) continue;

                $ret['columns'][$field_config->schema_type] = [];
                foreach ($field_schema['columns'] as $clmn => $clmn_info) {
                    $ret['columns'][$field_config->schema_type][$clmn] = $clmn_info['type'];
                }
            }
            $application->getPlatform()->setCache($ret, 'entity_field_schema', 0);
        }

        return $ret[$key];
    }

    public function clearFieldSchemaCache(Application $application)
    {
        $application->getPlatform()->deleteCache('entity_field_schema');
    }
}
