<?php
namespace SabaiApps\Directories\Component\Entity\Helper;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Request;
use SabaiApps\Directories\Exception;
use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Field;
use SabaiApps\Directories\Component\Form\FormComponent;

class FormHelper
{
    public function help(Application $application, $entityOrBundle, array $options = [])
    {
        $options += array(
            'values' => null,
            'values_check_build_id' => true,
            'is_admin' => false,
            'wrap' => [],
            'token' => 'entity_form',
            'pre_render_display' => false,
            'language' => null,
            'horizontal' => false,
            'fields_only' => false,
            'field_name_prefix' => '',
        );
        settype($options['wrap'], 'array');

        $entity = null;
        if ($entityOrBundle instanceof Entity\Type\IEntity) {
            if (!$bundle = $application->Entity_Bundle($entityOrBundle)) {
                throw new Exception\RuntimeException('Bundle not found: ' . $entityOrBundle->getBundleName());
            }
            if ($entityOrBundle->getId()) {
                $entity = $entityOrBundle;
            }
        } elseif ($entityOrBundle instanceof Entity\Model\Bundle) {
            $bundle = $entityOrBundle;
        } else {
            if (!$bundle = $application->Entity_Bundle($entityOrBundle)) {
                throw new Exception\RuntimeException('Invalid bundle: ' . $entityOrBundle);
            }
        }

        // Load field values if an existing entity has been passed
        if (isset($entity)) {
            $application->Entity_Field_load($entity->getType(), array($entity->getId() => $entity), true, false);
        }
        if (isset($options['values'])) {
            if ((isset($options['values'][FormComponent::FORM_BUILD_ID_NAME]) && Request::isPostMethod())
                || !empty($options['is_admin'])
            ) {
                // The values are supplied here to check if fields have been added dynamically before submit.
                // Form fields will be populated after the form submission is validated, so do not populate them here.
                $do_not_populate_fields = true;

                // Unwrap if wrapped
                if ($options['wrap']) {
                    foreach ($options['wrap'] as $wrap) {
                        if (!isset($options['values'][$wrap])) {
                            $options['values'] = null;
                            break;
                        } else {
                            $options['values'] = $options['values'][$wrap];
                        }
                    }
                }
            } else {
                $options['values'] = null;
            }
        }

        $fields = [];
        foreach ($bundle->Fields->with('FieldConfig') as $field) {
            // Remove field if field config does not exist for some reason
            if (!$field->FieldConfig) {
                $field->markRemoved()->commit();
                continue;
            }

            if (!$field->getFieldWidget()
                || $field->getFieldData('_no_ui')
                || $field->getFieldData('disabled')
                || (!$field_type = $application->Field_Type($field->getFieldType(), true))
                || (!$options['is_admin'] && ($field_type->fieldTypeInfo('admin_only') || $field->getFieldData('_admin_only')))
                || ($options['fields_only'] && is_array($options['fields_only']) && !in_array($field->getFieldName(), $options['fields_only']))
            ) continue;

            if ($property = $field->isPropertyField()) {
                switch ($property) {
                    case 'title':
                        if (!empty($bundle->info['no_title'])) continue 2;
                        break;
                    case 'content':
                        if (!empty($bundle->info['no_content'])) continue 2;
                        break;
                }
            }

            $fields[$field->getFieldName()] = $field;
        }

        $form = [
            '#class' => 'drts-entity-form',
            '#bundle' => $bundle,
            '#entity' => $entity,
            '#entity_field_max_num_items' => [],
        ];
        $_fields = [];
        foreach ($fields as $field_name => $field) {
            $field_value = null;
            if (isset($options['values'][$field_name])) {
                if (is_array($options['values'][$field_name])
                    && array_key_exists(0, $options['values'][$field_name])
                ) {
                    $field_value = $options['values'][$field_name];
                }
            } elseif (isset($entity)) {
                if (false === $field_value = $entity->getFieldValue($field_name)) {
                    $field_value = null;
                }
            }

            if ($form_field = $this->_getField($application, $field, $entity, $field_value, $options['wrap'], $options['field_name_prefix'], empty($do_not_populate_fields), $options['language'])) {
                if ($options['is_admin']) {
                    if ($application->Field_Type($field->getFieldType())->fieldTypeInfo('admin_only')) {
                        $form_field['#admin_only'] = true; // add this so that backend only fields can be displayed differently in the backend
                        // Load title form info since title for admin only fields cannot be edited
                        $form_field['#title'] = $application->Field_Type($field->getFieldType())->fieldTypeInfo('label');
                    }
                } else {
                    if (!empty($form_field['#admin_only'])) {
                        continue;
                    }
                }
                if (!empty($options['horizontal'])) {
                    $form_field['#horizontal'] = true;
                }
                $form[$options['field_name_prefix'] . $field_name] = $form_field;
                $form['#entity_field_max_num_items'][$field_name] = $field->getFieldMaxNumItems();
                $_fields[$field_name] = $field;
            }
        }

        if ($options['pre_render_display']) {
            $application->callHelper(
                'Display_Render_preRender',
                array($this->getDisplay($application, $bundle), $bundle, &$form)
            );
        }

        // Allow components to modify form
        $form = $application->Filter('entity_form', $form, array($bundle, $entity, $options));

        if ($options['wrap']) {
            $_form = [
                '#class' => $form['#class'],
                '#bundle' => $bundle,
                '#entity' => $entity,
                '#entity_field_max_num_items' => $form['#entity_field_max_num_items'],
            ];
            $__form =& $_form;
            foreach ($options['wrap'] as $wrap) {
                $__form[$wrap] = [
                    '#tree' => true,
                ];
                $__form =& $__form[$wrap];
            }
            $__form += $form;
            unset($__form['#class'], $__form['#bundle'], $__form['#entity'], $__form['#entity_field_max_num_items']);
            $form = $_form;
        }
        $form['#wrap'] = $options['wrap'];
        $form['#field_name_prefix'] = $options['field_name_prefix'];
        $form['#fields'] = $_fields;
        $form['#inherits'] = array('entity_form');
        $form['#render_tabs'] = false;
        if (!empty($options['token'])) {
            $form['#token_id'] = $options['token'];
        } else {
            $form['#token'] = false;
        }

        return $form;
    }

    public function render(Application $application, $form)
    {
        $rendered = $this->renderDisplay($application, $form);
        if (!$form = $rendered['form']) {
            $application->logWarning('Failed rendering form.');
            return;
        }

        $application->Action('entity_form_render', [$form]);

        return implode(PHP_EOL, array(
            $form->getHeaderHtml(),
            $form->getFormTag(),
            $rendered['html'],
            $form->getHtml(FormComponent::FORM_SUBMIT_BUTTON_NAME),
            $form->getHiddenHtml(),
            '</form>',
            $form->getJsHtml(),
        ));
    }

    public function renderDisplay(Application $application, $form, array $options = [])
    {
        if (isset($form['#bundle'])) { // prevent fatal error when #bundle is null for some reason
            $bundle = $form['#bundle'];
            $display = $this->getDisplay($application, $bundle);
            $application->callHelper('Display_Render_preRender', [$display, $bundle, &$form]);
        }
        $form = $application->Form_Render($form);
        $html = '';
        if (isset($display)
            && ($rendered = $application->Display_Render($bundle, $display, $form, $options))
        ) {
            $html = $rendered;
        }
        return ['form' => $form, 'html' => $html];
    }

    public function getDisplay(Application $application, Entity\Model\Bundle $bundle, $name = 'default', $useCache = true, $checkFields = false)
    {
        if (!$display = $this->hasDisplay($application, $bundle, $name, $useCache)) {
            $application->Display_Create($bundle, 'form', $name, array(
                'elements' => array_values($this->_getValidDisplayElements($application, $bundle)),
            ));

            // Attempt to fetch the display again
            if (!$display = $application->Display_Display($bundle->name, $name, 'form', false)) {
                throw new Exception\RuntimeException('Failed loading form display for ' . $bundle->name);
            }
        } else {
            if ($checkFields) {
                $reload_required = false;

                // Get current element names in display
                $current_elements = [];
                foreach (array_keys($display['elements']) as $element_id) {
                    $this->_getRecursiveElementNames($display['elements'][$element_id], $current_elements);
                }
                // Create field element if the element does not exist in display
                $elements_required = $this->_getValidDisplayElements($application, $bundle);
                foreach (array_keys($elements_required) as $field_name) {
                    if (!isset($current_elements[$field_name])) {
                        // The field element does not exist in display, so create it
                        $application->Display_Create_element($bundle, $display['name'], $elements_required[$field_name], 'form');

                        $reload_required = true;
                    } else {
                        // Any duplicates?
                        if (count($current_elements[$field_name]) > 1) {
                            // Leave duplicates so that they will be removed
                            $current_elements[$field_name] = array_slice($current_elements[$field_name], 1, null, true);
                        } else {
                            unset($current_elements[$field_name]);
                        }
                    }
                }

                // Remove elements that do not have any associated field
                if (!empty($current_elements)) {
                    $element_ids = [];
                    foreach (array_keys($current_elements) as $field_name) {
                        foreach ($current_elements[$field_name] as $element_id) {
                            $element_ids[] = $element_id;
                        }
                    }
                    if (!empty($element_ids)) {
                        $application->getModel('Element', 'Display')
                            ->fetchByIds($element_ids)
                            ->delete(true);
                        $reload_required = true;
                    }
                }

                // Attempt to fetch the display again if reload requried
                if ($reload_required) {
                    if (!$display = $application->Display_Display($bundle->name, $name, 'form', false)) {
                        throw new Exception\RuntimeException('Failed loading form display for ' . $bundle->name);
                    }
                }
            }
        }

        return $display;
    }

    protected function _getRecursiveElementNames($element, &$names)
    {
        if (isset($element['settings']['field_name'])) {
            $names[$element['settings']['field_name']][$element['id']] = $element['id'];
        }
        if (!empty($element['children'])) {
            foreach (array_keys($element['children']) as $element_id) {
                $this->_getRecursiveElementNames($element['children'][$element_id], $names);
            }
        }
    }

    protected function _getValidDisplayElements(Application $application, Entity\Model\Bundle $bundle)
    {
        $field_types = $application->Field_Types();
        $elements = [];
        foreach ($bundle->Fields->with('FieldConfig') as $field) {
            $field_type = $field->getFieldType();
            if (isset($elements[$field->getFieldName()])
                || !isset($field_types[$field_type])
                || $field->getFieldData('_no_ui')
                || $field_types[$field_type]['admin_only']
                || empty($field_types[$field_type]['widgets'])
                || ($field_type === 'entity_title' && !empty($bundle->info['no_title']))
                || (in_array($field_type, array('entity_content', 'wp_post_content')) && !empty($bundle->info['no_content']))
                || (in_array($field_type, array('entity_parent', 'wp_post_parent')) && empty($bundle->info['parent']))
                || (in_array($field_type, array('entity_term_parent')) && empty($bundle->info['is_hierarchical']))
            ) continue;

            $elements[$field->getFieldName()] = $field->toDisplayElementArray();
        }
        return $elements;
    }

    public function hasDisplay(Application $application, Entity\Model\Bundle $bundle, $name, $useCache = true)
    {
        return $application->Display_Display($bundle->name, $name, 'form', $useCache);
    }

    public function loadAssets(Application $application, Entity\Model\Bundle $bundle)
    {
        $cache_id = 'entity_form_assets_' . $bundle->name;
        // Files loaded depend on permissions. Need a better way...
        if ($application->getUser()->isAnonymous()) {
            $cache_id .= 0;
        } elseif ($application->getUser()->isAdministrator()) {
            $cache_id .= 1;
        } else {
            $cache_id .= 2;
        }
        if (false === $assets = $application->getPlatform()->getCache($cache_id)) {
            $application->getPlatform()->trackAssets(true, 'entity_form');
            $form = $this->help($application, $bundle);
            $this->renderDisplay($application, $form);
            $assets = $application->getPlatform()->getTrackedAssets('entity_form');
            $application->getPlatform()->trackAssets(false, 'entity_form');
            $application->getPlatform()->setCache($assets, $cache_id);
        }
        $application->getPlatform()->loadAssets($assets);
        $application->Action('entity_form_load_assets', [$bundle, $assets]);
    }

    public function clearAssetsCache(Application $application, $bundleName)
    {
        $application->getPlatform()->deleteCache('entity_form_assets_' . $bundleName);
    }

    protected function _getFieldStates(Application $application, Field\IField $field, $wrap)
    {
        if ((!$conditions = $field->getFieldConditions())
            || empty($conditions['add'])
            || empty($conditions['rules'])
        ) return;

        $rules = [];
        $field_prefix = null;
        if ($wrap) {
            $field_prefix = array_shift($wrap);
            if (!empty($wrap)) {
                $field_prefix .= '[' . implode('][', $wrap) . ']';
            }
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
            if ((!$_field = $application->Entity_Field($field->bundle_name, $field_name))
                || (!$field_type = $application->Field_Type($_field->getFieldType(), true))
                || !$field_type instanceof Field\Type\IConditionable
                || !$field_type->fieldConditionableInfo($_field, false)
                || (!$_rule = $field_type->fieldConditionableRule($_field, $rule['compare'], $rule['value'], $_name))
                || (!$_rule = $application->Filter('entity_field_condition_rule', ['name' => $_name] + $_rule, [$_field, $rule['compare'], $rule['value'], $_name, 'js']))
            ) continue;

            if (!isset($_rule['target'])) {
                $selector = $field_name;
                if ($field_prefix) $selector = $field_prefix . '[' . $selector . ']';
                $_rule['target'] = '[name^="' . $selector . '"]';
            }
            $rules[] = $_rule;
        }
        if (empty($rules)) return;

        $action = $conditions['action']['name'] === 'hide' ? 'invisible_disable' : 'visible_enable';
        if ($conditions['action']['match'] === 'any') {
            $action .= '_or';
        }
        return [$action => $rules];
    }

    protected function _getField(Application $application, Field\IField $field, Entity\Type\IEntity $entity = null, array $value = null, array $wrap = [], $fieldNamePrefix = '', $setValue = true, $language = null)
    {
        if (!$iwidget = $application->Field_Widgets_impl($field->getFieldWidget(), true)) return;

        $widget_info = $iwidget->fieldWidgetInfo();
        $hide_title = $field->getFieldData('hide_label');
        $field_title = $field->getFieldLabel(true);
        $form_field = array(
            '#tree' => true,
            '#title' => $hide_title  ? null : $field_title,
            '#title_hidden' => $hide_title ? $field_title : null,
            '#description' => $application->Htmlize($field->getFieldDescription(true), true), // allow inline HTML tags only
            '#description_no_escape' => true,
            '#weight' => $field->getFieldData('weight'),
            '#required' => $required = $field->isFieldRequired() && (!isset($widget_info['requirable']) || $widget_info['requirable']),
            '#display_required' => $required || $field->isFieldRequired(),
            '#states' => $this->_getFieldStates($application, $field, $wrap),
            '#class' => 'drts-entity-form-field ' . str_replace('_', '-', 'drts-entity-form-field-type-' . $field->getFieldType() . ' drts-entity-form-field-name-' . $field->getFieldName()),
        );

        if (empty($widget_info['accept_multiple'])) {
            if (!empty($widget_info['repeatable'])) {
                $max_num_items = $field->getFieldMaxNumItems();
                if (!empty($value)) {
                    $field_count = count($value);
                    if ($max_num_items && $max_num_items < $field_count) {
                        $field_count = $max_num_items;
                        $value = array_slice($value, 0, $field_count, true);
                    }
                    $next_index = 0;
                    foreach ($value as $key => $_value) {
                        if (!is_numeric($key)) continue;

                        if (!$form_field[$key] = $this->_doGetField($application, $field, $entity, $key, $setValue ? $_value : null, $wrap, $fieldNamePrefix, $language)) {
                            return;
                        }
                        ++$next_index;
                    }
                } else {
                    if (!$form_field[0] = $this->_doGetField($application, $field, $entity, 0, null, $wrap, $fieldNamePrefix, $language)) {
                        return;
                    }
                    $next_index = 1;
                }
                if ($max_num_items !== 1) {
                    $form_field['_add'] = array(
                        '#type' => 'addmore',
                        '#next_index' => $next_index,
                        '#max_num' => $max_num_items,
                    );
                }
            } else {
                if (!$_form_field = $this->_doGetField($application, $field, $entity, 0, $setValue && isset($value) ? array_shift($value) : null, $wrap, $fieldNamePrefix, $language)) {
                    return;
                }
                if (isset($_form_field['#type'])) {
                    switch ($_form_field['#type']) {
                        case 'hidden':
                            break;
                        case 'markup':
                            // prevent the form element from being rendered as a fieldset
                            $form_field = $_form_field;
                            break;
                        default:
                            $form_field[0] = $_form_field;
                    }
                } else {
                    $form_field[0] = $_form_field;
                }
            }
        } else {
            if (!$_form_field = $this->_doGetField($application, $field, $entity, null, $setValue ? $value : null, $wrap, $fieldNamePrefix, $language)) {
                return;
            }
            $form_field = [
                '#required' => $form_field['#required'],
                '#class' => $form_field['#class'],
                '#description_top' => true,
            ] + $_form_field + $form_field;
        }
        if (isset($form_field[0])) {
            // Make only the first element required if multiple input fields
            $form_field[0]['#required'] = $form_field['#required'];
            // Do not show as displayed since fieldset already shows it
            $form_field[0]['#display_required'] = false;

            if (!empty($form_field[0]['#disabled'])) {
                // Probably disabled at runtime because of permissions.
                // Need to mark container data disabled so that values for this field do not get submitted.
                $form_field['#disabled'] = true;
            }

            // Move container parameters to container array from the first element
            foreach (array('#title', '#description', '#admin_only', '#title_hidden') as $key) {
                if (array_key_exists($key, $form_field[0])) {
                    $form_field[$key] = $form_field[0][$key];
                    unset($form_field[0][$key]);
                }
            }
        }

        return $form_field;
    }

    protected function _doGetField(Application $application, Field\IField $field, Entity\Type\IEntity $entity = null, $key = null, $value = null, array $wrap = [], $fieldNamePrefix = '', $language = null)
    {
        $widget = $field->getFieldWidget();
        $iwidget = $application->Field_Widgets_impl($widget);
        // Init widget settings
        $widget_settings = $field->getFieldWidgetSettings() + (array)$iwidget->fieldWidgetInfo('default_settings');
        $parents = $wrap;
        $parents[] = $fieldNamePrefix . $field->getFieldName();
        if (isset($key)) {
            $parents[] = $key;
        }
        if (!$ele = $iwidget->fieldWidgetForm($field, $widget_settings, $value, $entity, $parents, $language)) {
            // do not display this form element
            return;
        }

        // Let other components to modify configuration
        if (!$ele = $application->Filter('entity_field_widget', $ele, array($entity, $field, $value, $widget, $widget_settings))) {
            // do not display this form element
            return;
        }

        if (!isset($entity)) {
            if (!isset($ele['#default_value'])) {
                if (null !== $default_value = $field->getFieldDefaultValue()) {
                    if ($iwidget->fieldWidgetInfo('accept_multiple')) {
                        $default_value = [$default_value];
                    } else {
                        if (strlen($default_value)) {
                            $ele['#placeholder'] = $default_value;
                        }
                    }
                    $ele['#default_value'] = $default_value;
                }
            }
        } else {
            if ($value === null
                && (!isset($ele['#entity_set_default_value']) || $ele['#entity_set_default_value'])
            ) { // set default value to null if no entity value
                $ele['#default_value'] = null;
            }
        }
        // Make the field not required by default. This will be overriden by the actual setting if needed.
        $ele['#required'] = false;

        return $ele;
    }
}
