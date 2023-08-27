<?php
namespace SabaiApps\Directories\Component\Search\Helper;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Exception;

class FieldsHelper
{
    public function help(Application $application, $useCache = true)
    {
        if (!$useCache
            || (!$fields = $application->getPlatform()->getCache('search_fields'))
        ) {
            $fields = [];
            foreach ($application->InstalledComponentsByInterface('Search\IFields') as $component_name) {
                if (!$application->isComponentLoaded($component_name)) continue;
                
                foreach ($application->getComponent($component_name)->searchGetFieldNames() as $field_name) {
                    if (!$field = $application->getComponent($component_name)->searchGetField($field_name)) {
                        continue;
                    }
                    $fields[$field_name] = array(
                        'weight' => $field->searchFieldInfo('weight'),
                        'component' => $component_name
                    );
                }
            }
            uasort($fields, function($a, $b) { return $a['weight'] < $b['weight'] ? -1 : 1; });
            foreach (array_keys($fields) as $field_name) {
                $fields[$field_name] = $fields[$field_name]['component'];
            }
            $application->getPlatform()->setCache($fields, 'search_fields');
        }
        
        return $fields;
    }
    
    private $_impls = [];

    /**
     * Gets an implementation of SabaiApps\Directories\Component\Search\Field\IField interface for a given field name
     * @param Application $application
     * @param string $field
     */
    public function impl(Application $application, $field, $returnFalse = false)
    {
        if (!isset($this->_impls[$field])) {            
            if ((!$fields = $this->help($application))
                || !isset($fields[$field])
                || !$application->isComponentLoaded($fields[$field])
            ) {                
                if ($returnFalse) return false;
                throw new Exception\UnexpectedValueException(sprintf('Invalid field: %s', $field));
            }
            $this->_impls[$field] = $application->getComponent($fields[$field])->searchGetField($field);
        }

        return $this->_impls[$field];
    }
    
    public function settingsForm(Application $application, $bundle, $fieldName, array $settings = [], array $parents = [])
    {
        if ((!$field = $this->impl($application, $fieldName, true))
            || !$field->searchFieldSupports($bundle)
        ) return;
        
        $default_settings = (array)$field->searchFieldInfo('default_settings');
        $form = array(
            '#title' => $field->searchFieldInfo('label'),
            '#title_no_escape' => true,
            '#tree' => true,
            'disabled' => array(
                '#weight' => -1,
                '#type' => 'checkbox',
                '#title' => __('Hide this field', 'directories'),
                '#default_value' => !empty($settings['disabled']),
                '#horizontal' => true,
            ),
            'settings' => array(
                //'#states' => array(
                //    'invisible' => array(
                //        sprintf('input[name="%s"]', $application->Form_FieldName(array_merge($parents, array('disabled')))) => array('type' => 'checked', 'value' => true),
                //    ),
                //),
                'form' => array(
                    '#title' => __('Form Field Settings', 'directories'),
                    '#class' => 'drts-form-label-lg',
                    '#weight' => 99,
                ),
            ),
        );
        if ($field->searchFieldInfo('requirable') !== false) {
            $form['settings']['required'] = [
                '#weight' => -1,
                '#type' => 'checkbox',
                '#title' => __('Required', 'directories'),
                '#default_value' => !empty($settings['settings']['required']),
                '#horizontal' => true,
            ];
        }
        $field_settings_form = $field->searchFieldSettingsForm(
            $bundle,
            $field_settings = isset($settings['settings']) ? $settings['settings'] + $default_settings : $default_settings,
            array_merge($parents, array('settings'))
        );
        $form['settings'] += $application->Filter(
            'search_field_settings',
            $field_settings_form,
            array($bundle, $fieldName, $field_settings)
        );
        if (isset($default_settings['form']['icon'])) {
            $form['settings']['form']['icon'] = array(
                '#title' => __('Field icon', 'directories'),
                '#type' => 'iconpicker',
                '#default_value' => isset($settings['settings']['form']['icon']) ? $settings['settings']['form']['icon'] : $default_settings['form']['icon'],
                '#horizontal' => true,
                '#weight' => 1,
            );
        }
        if (isset($default_settings['form']['placeholder'])) {
            $form['settings']['form']['placeholder'] = array(
                '#title' => __('Placeholder text', 'directories'),
                '#type' => 'textfield',
                '#default_value' => isset($settings['settings']['form']['placeholder']) ? $settings['settings']['form']['placeholder'] : $default_settings['form']['placeholder'],
                '#horizontal' => true,
                '#weight' => 5,
            );
        }
        $form['settings']['form']['order'] = array(
            '#title' => __('Display order', 'directories'),
            '#type' => 'slider',
            '#default_value' => isset($settings['settings']['form']['order']) ? $settings['settings']['form']['order'] : @$default_settings['form']['order'],
            '#horizontal' => true,
            '#min_value' => 1,
            '#max_value' => 5,
            '#integer' => true,
            '#weight' => 10,
        );
        
        return $form;
    }
}