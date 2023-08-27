<?php
namespace SabaiApps\Directories\Component\View\DisplayElement;

use SabaiApps\Directories\Exception;
use SabaiApps\Directories\Component\Display;
use SabaiApps\Directories\Component\Field;
use SabaiApps\Directories\Component\Entity\Model\Bundle;

class FilterDisplayElement extends Display\Element\AbstractElement
{
    protected function _getField($bundleName)
    {
        $field_name = substr($this->_name, 12); // remove view_filter_ part
        if (!$field = $this->_application->Entity_Field($bundleName, $field_name)) {
            throw new Exception\RuntimeException(sprintf('Invalid field %s for bundle %s', $field_name, $bundleName));
        }
        return $field;
    }

    protected function _getFilter($bundleName, $filterName, $displayName, Field\IField $field = null)
    {
        if (!isset($field)) {
            $field = $this->_getField($bundleName);
        }
        if (!$filter = $this->_application->getModel('Filter', 'View')->displayName_is($displayName)->fieldId_is($field->getFieldId())->name_is($filterName)->fetchOne()) {
            throw new Exception\RuntimeException(sprintf('Invalid filter %s for bundle %s', $filterName, $bundleName));
        }
        return $filter;
    }

    protected function _displayElementInfo(Bundle $bundle)
    {
        $field = $this->_getField($bundle->name);
        return [
            'type' => 'field',
            'label' => $field->getFieldLabel(),
            'description' => sprintf(__('Field name: %s', 'directories'), $field->getFieldName()),
            'default_settings' => [],
            'icon' => $this->_application->Field_Type($field->getFieldType())->fieldTypeInfo('icon'),
            'headingable' => false,
        ];
    }

    protected function _displayElementSupports(Bundle $bundle, Display\Model\Display $display)
    {
        if ($display->type !== 'filters') return false;

        try {
            $field = $this->_getField($bundle->name);
        } catch (\Exception $e) {
            $this->_application->LogError($e);
            return false;
        }

        if (($bundle_name = $field->getFieldData('_bundle_name'))
            && (!$this->_application->Entity_Bundle($bundle_name))
        ) {
            return false;
        }
        return true;
    }

    protected function _getFieldFilters(Field\IField $field)
    {
        $filters = [];
        $field_types = $this->_application->Field_Types();
        if (!empty($field_types[$field->getFieldType()]['filters'])) {
            $filters = $field_types[$field->getFieldType()]['filters'];
            foreach (array_keys($filters) as $filter_type) {
                if ((!$field_filter = $this->_application->Field_Filters_impl($filter_type, true))
                    || !$field_filter->fieldFilterSupports($field)
                ) {
                    unset($filters[$filter_type]);
                }
            }
        }
        return $filters;
    }

    public function displayElementSettingsForm(Bundle $bundle, array $settings, Display\Model\Display $display, array $parents = [], $tab = null, $isEdit = false, array $submitValues = [])
    {
        $field = $this->_getField($bundle->name);
        $filters = $this->_getFieldFilters($field);

        // Make sure the current filter is valid if any
        $filter = null;
        if (isset($settings['filter_name'])) {
            $display_name = isset($settings['display_name']) ? $settings['display_name'] : 'default';
            $filter = $this->_getFilter($bundle->name, $settings['filter_name'], $display_name, $field);
            if (!isset($filters[$filter->type])) {
                // This filter is invalid or has become invalid
                $filter->markRemoved()->commit();
                throw new Exception\RuntimeException('Invalid filter type ' . $filter->type . ' for field type ' . $field->getFieldType());
            }
        }

        if (empty($filters)) {
            throw new Exception\RuntimeException('No filter is avaialbe for field type ' . $field->getFieldType());
        }

        if ($tab === 'conditions') {
            $conditions = [];
            $field_types = $this->_application->Field_Types();
            foreach ($this->_application->getModel('Filter', 'View')
                ->bundleName_is($bundle->name)
                ->displayName_is($display->name)
                ->fetch()
                ->with('Field', 'FieldConfig'
            ) as $_filter) {
                if ((!$ifilter = $this->_application->Field_Filters_impl($_filter->type, true))
                    || !$ifilter instanceof Field\Filter\IConditionable
                    || (!$_field = $_filter->getField())
                    || !isset($field_types[$_field->getFieldType()])
                    || empty($field_types[$_field->getFieldType()]['filters'])
                    || $_field->getFieldName() === $field->getFieldName()
                    || (!$condition_info = $ifilter->fieldFilterConditionableInfo($_field))
                ) continue;

                foreach (array_keys($condition_info) as $name) {
                    $option_name = strlen($name) ? $_filter->name . ',' . $name : $_filter->name;
                    $conditions[$option_name] = $condition_info[$name];
                    if (isset($conditions[$option_name]['label'])) {
                        $conditions[$option_name]['label'] = $_field->getFieldLabel() . ' - ' . $conditions[$option_name]['label'];
                    } else {
                        $conditions[$option_name]['label'] = $_field->getFieldLabel();
                    }
                }
            }
            if (empty($conditions)) return;

            $condition_settings = $filter ? $filter->getFilterConditions() : [];
            $form = [
                'add' => [
                    '#type' => 'checkbox',
                    '#title' => __('Add conditional rules', 'directories'),
                    '#default_value' => !empty($condition_settings['add']),
                    '#horizontal' => true,
                ],
                'action' => [
                    '#horizontal' => true,
                    '#row' => true,
                    '#title' => ' ',
                    'name' => [
                        '#type' => 'select',
                        '#options' => [
                            'show' => _x('Show', 'conditional rule', 'directories'),
                            'hide' => _x('Hide', 'conditional rule', 'directories'),
                        ],
                        '#default_value' => isset($condition_settings['action']['name']) ? $condition_settings['action']['name'] : 'show',
                        '#weight' => 1,
                        '#col' => 3,
                    ],
                    'match' => [
                        '#type' => 'select',
                        '#options' => [
                            'all' => __('if all of the following match', 'directories'),
                            'any' => __('if any of the following matches', 'directories'),
                        ],
                        '#default_value' => isset($condition_settings['action']['match']) ? $condition_settings['action']['match'] : 'all',
                        '#weight' => 2,
                        '#col' => 9,
                    ],
                    '#states' => [
                        'visible' => [
                            sprintf('input[name="%s"]', $this->_application->Form_FieldName(array_merge($parents, ['add']))) => ['type' => 'checked', 'value' => true],
                        ],
                    ],
                ],
                'rules' => [
                    '#title' => ' ',
                    '#horizontal' => true,
                    '#states' => [
                        'visible' => [
                            sprintf('input[name="%s"]', $this->_application->Form_FieldName(array_merge($parents, ['add']))) => ['type' => 'checked', 'value' => true],
                        ],
                    ],
                ],
            ];
            if (isset($submitValues['rules'])) {
                // coming from form submission
                // need to check request values since fields may have been added/removed
                $rules = empty($submitValues['rules']) ? [null] : $submitValues['rules'];
            } else {
                if (!empty($condition_settings['rules'])) {
                    $rules = $condition_settings['rules'];
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
                    '#label_select' => __('Select Filter', 'directories'),
                );
            }
            $form['rules']['_add'] = [
                '#type' => 'addmore',
                '#next_index' => ++$i,
            ];
            return $form;
        }

        $form = $this->_application->Display_ElementLabelSettingsForm($settings, $parents) + array(
            'label_as_heading' => array(
                '#title' => __('Show label as heading', 'directories'),
                '#type' => 'checkbox',
                '#default_value' => !empty($settings['label_as_heading']),
                '#horizontal' => true,
                '#states' => array(
                    'invisible' => array(
                        sprintf('select[name="%s[label]"]', $this->_application->Form_FieldName($parents)) => array('value' => 'none'),
                    ),
                ),
                '#weight' => -4,
            ),
            'filter' => array(
                '#type' => 'select',
                '#title' => __('Filter type', 'directories'),
                '#options' => $filters,
                '#weight' => -1,
                '#default_value' => isset($filter) ? $filter->type : null,
                '#horizontal' => true,
            ),
            'filter_settings' => array(
                '#tree' => true,
            ),
        );
        if (count($filters) === 1) {
            if (!isset($form['filter']['#default_value'])) {
                $filter_names = array_keys($filters);
                $form['filter']['#default_value'] = $filter_names[0];
            }
            $form['filter']['#type'] = 'hidden';
        }
        foreach (array_keys($filters) as $filter_type) {
            $field_filter = $this->_application->Field_Filters_impl($filter_type);
            $filter_settings = isset($filter) && $filter->type === $filter_type ? $filter->data['settings'] : [];
            $filter_settings += (array)$field_filter->fieldFilterInfo('default_settings');
            $filter_settings_parents = $parents;
            $filter_settings_parents[] = 'filter_settings';
            $filter_settings_parents[] = $filter_type;
            $filter_settings_form = $field_filter->fieldFilterSettingsForm($this->_getField($bundle->name), $filter_settings, $filter_settings_parents);
            if ($filter_settings_form) {
                $form['filter_settings'][$filter_type] = $filter_settings_form;
                foreach (array_keys($form['filter_settings'][$filter_type]) as $key) {
                    if (false === strpos($key, '#')) {
                        $form['filter_settings'][$filter_type][$key]['#horizontal'] = true;
                    }
                }
                $form['filter_settings'][$filter_type]['#states']['visible'] = array(
                    sprintf('[name="%s[filter]"]', $this->_application->Form_FieldName($parents)) => array('value' => $filter_type),
                );
            }
        }

        $form['name'] = array(
            '#type' => 'textfield',
            '#title' => __('Filter name', 'directories'),
            '#description' => __('Enter a machine readable name which may not be changed later. Only lowercase alphanumeric characters and underscores are allowed.', 'directories'),
            '#max_length' => 255,
            '#required' => true,
            '#weight' => -99,
            '#regex' => '/^[a-z0-9_]+$/',
            '#field_prefix' => 'filter_',
            '#horizontal' => true,
            '#slugify' => true,
            '#default_value' => isset($filter)
                ? (strpos($filter->name, 'filter_') === 0 ? substr($filter->name, 7) : $filter->name)
                : $field->getFieldName(),
        );
        if (!isset($filter)) {
            $form['name']['#states']['slugify'] = array(
                sprintf('input[name="%s[label_custom]"]', $this->_application->Form_FieldName($parents)) => array('type' => 'filled', 'value' => true),
            );
        } else {
            $form['filter_id'] = array(
                '#type' => 'hidden',
                '#value' => $filter->id,
            );
        }

        $form['#tabs'] = [
            'conditions' => __('Conditions', 'directories'),
        ];

        return $form;
    }

    public function isCustomLabelRequired($form, $parents)
    {
        $form_values = $form->getValue($parents);
        return $form_values['label'] === 'custom';
    }

    public function displayElementRender(Bundle $bundle, array $element, $var)
    {
        $settings = $element['settings'];
        if (!$html = $var->render()->getHtml($settings['filter_name'])) return;

        $html = '<div class="drts-view-filter-field">' . $html . '</div>';

        if (isset($settings['label'])) {
            $label_type = $settings['label'];
            $label = $this->_application->Display_ElementLabelSettingsForm_label(
                $settings,
                $this->displayElementStringId('label', $element['_element_id']),
                $this->_getField($bundle->name)->getFieldLabel(true)
            );
            if (strlen($label)) {
                if (empty($settings['label_as_heading'])) {
                    $heading_class = '';
                } else {
                    $heading_class = ' drts-display-element-header';
                    $label = '<span>' . $label . '</span>';
                }

                $html = '<div class="drts-view-filter-field-label drts-view-filter-field-label-type-' . $label_type . $heading_class . '">'
                    . $label . '</div>' . $html;
            }
        }

        if (empty($settings['conditions']['rules'])) return $html;

        return [
            'id' => 'drts-view-filter-display-element-' . $element['element_id'],
            'html' => $html,
        ];
    }

    public function displayElementTitle(Bundle $bundle, array $element)
    {
        return $this->_application->Display_ElementLabelSettingsForm_label($element['settings'], null, $this->_getField($bundle->name)->getFieldLabel());
    }

    public function displayElementOnRemoved(Bundle $bundle, array $settings, $elementName, $elementId)
    {
        if (0 !== strpos($settings['filter_name'], 'filter_')) return; // default filters may not be removed

        $display_name = isset($settings['display_name']) ? $settings['display_name'] : 'default';
        $this->_getFilter($bundle->name, $settings['filter_name'], $display_name)->markRemoved()->commit();
    }

    public function displayElementOnPositioned(Bundle $bundle, array $settings, $weight)
    {
        $display_name = isset($settings['display_name']) ? $settings['display_name'] : 'default';
        $filter = $this->_getFilter($bundle->name, $settings['filter_name'], $display_name);
        $filter->data = array('weight' => $weight) + $filter->data;
        $filter->commit();
    }

    public function displayElementOnCreate(Bundle $bundle, array &$data, $weight, Display\Model\Display $display, $elementName, $elementId)
    {
        $settings = $data['settings'];
        $filter = null;
        if (!empty($settings['filter_id'])
            && (!$filter = $this->_application->getModel('Filter', 'View')->fetchById($settings['filter_id']))
        ) {
            throw new Exception\RuntimeException('Invalid filter id'); // this should not happen
        }

        // Make sure the filter name is unique
        $filter_name = isset($settings['filter_name']) ? $settings['filter_name'] : 'filter_' . $settings['name'];
        $name_query = $this->_application->getModel('Filter', 'View')->bundleName_is($bundle->name)->displayName_is($display->name)->name_is($filter_name);
        if ($filter) {
            $name_query->id_isNot($filter->id);
        }
        if ($name_query->count() > 0) {
            throw new Exception\RuntimeException(__('The name is already in use by another field.', 'directories') . ' - ' . $filter_name);
        }

        // Make sure the field is filterable
        $field = $this->_getField($bundle->name);
        $field_types = $this->_application->Field_FilterableFieldTypes($bundle);
        if (!isset($field_types[$field->getFieldType()])
            || !isset($field_types[$field->getFieldType()]['filters'][$settings['filter']])
        ) {
            throw new Exception\RuntimeException(__('The field is not filterable.', 'directories'));
        }

        // Create or update filter
        if (!$filter) {
            $filter = $this->_application->getModel(null, 'View')->create('Filter')->markNew();
            $filter->field_id = $field->id;
            $filter->bundle_name = $field->bundle_name;
            $filter->display_name = $display->name;
        }
        // Conditions?
        $conditions = [];
        if (!empty($settings['conditions']['rules'])
            && ($settings['conditions']['rules'] = array_filter($settings['conditions']['rules']))
        ) {
            $conditions = $settings['conditions'];
        }
        // Extract filter settings
        $filter_settings = isset($settings['filter_settings'][$settings['filter']]) ? $settings['filter_settings'][$settings['filter']] : [];
        unset($data['settings']['filter_settings']);
        $filter->type = $settings['filter'];
        $filter->name = $filter_name;
        $filter->data = array(
            'settings' => $filter_settings,
            'conditions' => $conditions,
            'element_id' => $elementName . '-' . $elementId,
        ) + (array)$filter->data;
        $filter->commit();

        $data['settings'] += array(
            'field_name' => $field->getFieldName(),
            'filter_name' => $filter->name,
            'display_name' => $filter->display_name,
        );

        $this->_application->Entity_Facets_clearCache();
    }

    public function displayElementOnUpdate(Bundle $bundle, array &$data, Display\Model\Element $element)
    {
        if ($element->Display) {
            $this->displayElementOnCreate($bundle, $data, $element->weight, $element->Display, $element->name, $element->element_id);
        }
    }

    public function displayElementOnSaved(Bundle $bundle, Display\Model\Element $element)
    {
        if (isset($element->data['settings']['label'])
            && in_array($element->data['settings']['label'], array('custom', 'custom_icon'))
        ) {
            $this->_registerString($element->data['settings']['label_custom'], 'label', $element->element_id);
        } else {
            $this->_unregisterString('label', $element->id);
        }
        $this->_unregisterString('label', $element->id); // for old versions
    }

    public function displayElementOnExport(Bundle $bundle, array &$data)
    {
        $settings = $data['settings'];

        if (!isset($settings['filter_name'])) {
            throw new Exception\RuntimeException('Failed exporting filter');
        }
        $display_name = isset($settings['display_name']) ? $settings['display_name'] : 'default';
        $filter = $this->_getFilter($bundle->name, $settings['filter_name'], $display_name);
        $data['settings']['filter_settings'][$settings['filter']] = $filter->data['settings'];
        // Unset filter ID so that the filter is created on import
        unset($data['settings']['filter_id']);
    }

    protected function _displayElementReadableInfo(Bundle $bundle, Display\Model\Element $element)
    {
        $settings = $element->data['settings'];
        $ret = [
            'filter_name' => [
                'label' => __('Filter name', 'directories'),
                'value' => $settings['filter_name'],
            ],
        ];
        try {
            $display_name = isset($settings['display_name']) ? $settings['display_name'] : 'default';
            if (($field = $this->_getField($bundle->name))
                && ($filters = $this->_getFieldFilters($field))
                && ($filter = $this->_getFilter($bundle->name, $settings['filter_name'], $display_name, $field))
                && isset($filters[$filter->type])
            ) {
                $ret['filter'] = [
                    'label' => __('Form field type', 'directories'),
                    'value' => $filters[$filter->type],
                ];
            }
        } catch (Exception\IException $e) {
            $this->_application->LogError($e);
        }
        return ['settings' => ['value' => $ret]];
    }

    public function displayElementIsDisabled(Bundle $bundle, array $settings)
    {
        return ($field = $this->_getField($bundle->name)) && $field->getFieldData('disabled');
    }
}
