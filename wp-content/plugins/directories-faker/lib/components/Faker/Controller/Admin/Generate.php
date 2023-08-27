<?php
namespace SabaiApps\Directories\Component\Faker\Controller\Admin;

use SabaiApps\Directories\Context;
use SabaiApps\Directories\Request;
use SabaiApps\Directories\Exception;
use SabaiApps\Directories\Component\Form;
use SabaiApps\Directories\Component\Field;

class Generate extends Form\AbstractMultiStepController
{    
    protected function _getBundle(Context $context)
    {
        return $context->bundle;
    }
    
    protected function _getSteps(Context $context, array &$formStorage)
    {
        return array('select_fields' => [], 'generator_settings' => [], 'generate' => []);
    }
    
    public function _getFormForStepSelectFields(Context $context, array &$formStorage)
    {        
        $options = $options_disabled = [];
        $generators = $this->Faker_Generators(true);
        $bundle = $this->_getBundle($context);
        $fields = $this->Entity_Field($bundle->name);
        foreach ($fields as $field_name => $field) {
            if (empty($generators[$field->getFieldType()])) continue;
            
            foreach ($generators[$field->getFieldType()] as $generator_name) {            
                if (($generator = $this->Faker_Generators_impl($generator_name, true))
                    && $generator->fakerGeneratorSupports($bundle, $field)
                ) {
                    $options[$field_name]['field'] = $this->_getFieldLabel($field);
                    if ($field->isPropertyField()) {
                        $options_disabled[$field_name] = $field_name;
                    }
                    break;
                }
            }
        }
        
        return array(
            '#header' => [
                [
                    'level' => 'info',
                    'message' => sprintf(__('Select the fields to generate for %s.', 'directories-faker'), $bundle->getLabel()),
                ],
            ],
            'fields' => array(
                '#type' => 'tableselect',
                '#header' => array(
                    'field' => __('Field name', 'directories-faker'),
                ),
                '#multiple' => true,
                '#js_select' => true,
                '#options' => $options,
                '#options_disabled' => $options_disabled,
                '#default_value' => array_keys($options),
                '#required' => empty($options_disabled),
                '#element_validate' => [function(Form\Form $form, &$value, $element) {
                    $value = array_merge($element['#options_disabled'], (array)$value);
                }],
            ),
        );
    }
    
    protected function _getFieldLabel(Field\IField $field)
    {
        $label = $this->H($field->getFieldLabel()) . ' (' . $field->getFieldName() . ')';
        if ($field->isCustomField()) {
            $label = '<span class="drts-bs-badge drts-bs-badge-secondary">' . $this->H(__('Custom field', 'directories-faker')) . '</span> ' . $label;
        }
        return $label;
    }
    
    public function _getFormForStepGeneratorSettings(Context $context, array &$formStorage)
    {     
        $form = array('settings' => []);

        $bundle = $this->_getBundle($context);
        $fields = $this->Entity_Field($bundle->name);
        $generators_by_field_type = $this->Faker_Generators(true);
        $selected_fields = (array)@$formStorage['values']['select_fields']['fields'];
        foreach ($selected_fields as $field_name) {
            
            if (!$field = @$fields[$field_name]) continue;
                    
            if (!$generators = @$generators_by_field_type[$field->getFieldType()]) continue;
            
            $last_valid_generator_name = null;
            foreach ($generators as $generator_name) {            
                if ((!$generator = $this->Faker_Generators_impl($generator_name, true))
                    || !$generator->fakerGeneratorSupports($bundle, $field)
                ) {
                    continue;
                }
                
                if (!isset($form['settings'][$field_name]['generator'])) {
                    $form['settings'][$field_name]['generator'] = array(
                        '#type' => 'radios',
                        '#title' => __('Field generator', 'directories-faker'),
                        '#options' => [],
                        '#horizontal' => true,
                        '#default_value' => $field->getFieldType(),
                        '#default_value_auto' => true,
                    );
                    $form['settings'][$field_name]['generator_settings'] = [];
                }
                
                $info = $generator->fakerGeneratorInfo();
                $form['settings'][$field_name]['generator']['#options'][$generator_name] = isset($info['label']) ? $info['label'] : __('Default', 'directories-faker');
                if (isset($info['description'])) {
                    $form['settings'][$field_name]['generator']['#options_description'][$generator_name] = $info['description'];
                }
                if ($settings_form = $generator->fakerGeneratorSettingsForm($field, (array)@$info['default_settings'], array('settings', $field_name, 'generator_settings', $generator_name))) {
                    foreach (array_keys($settings_form) as $key) {
                        if (strpos($key, '#') !== 0 ) {
                            $settings_form[$key]['#horizontal'] = true;
                        }
                    }
                    $form['settings'][$field_name]['generator_settings'][$generator_name] = $settings_form;
                    $form['settings'][$field_name]['generator_settings'][$generator_name]['#states']['visible'] = array(
                        sprintf('[name="%s[generator]"]', $this->Form_FieldName(array('settings', $field_name))) => array('value' => $generator_name),
                    );
                }
                $last_valid_generator_name = $generator_name;
            }
            
            if (!empty($form['settings'][$field_name])) {
                if (!empty($form['settings'][$field_name]['generator_settings'])) {
                    $form['settings'][$field_name]['#title'] = $this->_getFieldLabel($field);
                    $form['settings'][$field_name]['#title_no_escape'] = true;
                }
                
                if (count($form['settings'][$field_name]['generator']['#options']) === 1) {
                    $form['settings'][$field_name]['generator']['#type'] = 'hidden';
                    $form['settings'][$field_name]['generator']['#value'] = $last_valid_generator_name;
                }
            }
        }
        
        if (empty($form['settings'])) {
            return $this->_skipStepAndGetForm($context, $formStorage);
        }
        
        $form['settings']['#tree'] = true;
        $form['#header'][] = [
            'level' => 'info',
            'message' => __('Please configure additional options for each field.', 'directories-faker'),
        ];
        
        return $form;
    }
    
    public function _getFormForStepGenerate(Context $context, array &$formStorage)
    {
        $this->_initProgress($context, __('Generating...', 'directories-faker'));
        $this->_submitButtons[] = array('#btn_label' => __('Generate Now', 'directories-faker'), '#btn_color' => 'primary', '#btn_size' => 'lg');
        
        return array(
            'num' => array(
                '#type' => 'number',
                '#title' => __('Number of item to generate', 'directories-faker'),
                '#default_value' => 100,
                '#min_value' => 1,
                '#integer' => true,
                '#horizontal' => true,
                '#required' => true,
            ),
            'limit_request' => array(
                '#type' => 'number',
                '#title' => __('Number of records to process per request', 'directories-faker'),
                '#description' => __('Adjust this setting if you are experiencing timeout errors.', 'directories-faker'),
                '#default_value' => 10,
                '#min_value' => 1,
                '#integer' => true,
                '#horizontal' => true,
            ),
        );
    }
    
    public function _submitFormForStepGenerate(Context $context, Form\Form $form)
    {
        @set_time_limit(0);

        $start_time = microtime(true);
        $selected_fields = $form->storage['values']['select_fields']['fields'];
        $generator_settings = (array)@$form->storage['values']['generator_settings']['settings'];
        $bundle = $this->_getBundle($context);
        $total = $form->values['num'];
        $fields = $this->Entity_Field($bundle->name);
        $num_created = isset($form->storage['num_created']) ? $form->storage['num_created'] : 0;
        $failed = isset($form->storage['failed']) ? $form->storage['failed'] : [];
        $start_num = $num_created + count($failed);
        $limit = (int)$form->values['limit_request'];
        if ($start_num + $limit > $total) {
            $limit = $total - $start_num;
        }
        for ($i = 0; $i < $limit; $i++) {
            $values = $generators = [];
            foreach (array_keys($selected_fields) as $j) {
                $field_name = $selected_fields[$j];
                if (isset($values[$field_name]) // already assigned by another generator
                    || !isset($fields[$field_name])
                    || (!$_settings = @$generator_settings[$field_name])
                    || !isset($_settings['generator'])
                    || (!$generator = $this->Faker_Generators_impl($_settings['generator'], true)) 
                    || !$generator->fakerGeneratorSupports($bundle, $fields[$field_name])
                ) continue;
                
                $value = $generator->fakerGeneratorGenerate(
                    $fields[$field_name],
                    isset($_settings['generator_settings'][$_settings['generator']]) ? $_settings['generator_settings'][$_settings['generator']] : [],
                    $values,
                    $form->storage
                );
                if ($value === null) {
                    continue;
                }
                
                if ($value === false) {
                    unset($selected_fields[$j]); // an error occurred with this generator, so skip this field for subsequent items
                    continue;
                }
                 
                $values[$field_name] = $value;
                $generators[$field_name] = $_settings['generator'];
            }
            
            try {
                if (empty($values['post_author'])) {
                    $values['post_author'] = 0;
                }
                $entity = $this->Entity_Save($bundle, $values);
                ++$num_created;
                
                // Notify
                $this->Action('faker_generate_entity', array($bundle, $entity, $values, $generator_settings));
            } catch (\Exception $e) {
                $failed[$start_num + $i + 1] = $e->getMessage();
            }
        }

        $end_time = microtime(true);
        $done = $num_created + count($failed);
        $message = __('Generating...', 'directories-faker');
        if ($limit <= 1) {
            $message .= sprintf(
                ' %d of %d items processed (%s seconds).',
                $done,
                $total,
                $end_time - $start_time
            );
        } else {
            $message .= sprintf(
                ' %d-%d of %d items processed (%s seconds).',
                $start_num + 1,
                $done,
                $total,
                $end_time - $start_time
            );
        }

        $form->storage['num_created'] = $num_created;
        $form->storage['failed'] = $failed;

        if ($done < $total) {
            $this->_isInProgress($context, $done, $total, $message);
            return;
        }
    }

    protected function _complete(Context $context, array $formStorage)
    {
        $error = $success = [];
        if (!empty($formStorage['failed'])) {
            foreach ($formStorage['failed'] as $num => $error_message) {
                $error[] = sprintf(
                    $this->H(__('Item #%d could not be generated: %s', 'directories-faker')),
                    $num,
                    $error_message
                );
            }
        }
        if (!empty($formStorage['num_created'])) {
            $success[] = sprintf(
                $this->H(__('%d item(s) generated successfully.', 'directories-faker')),
                $formStorage['num_created']
            );
        }
        $context->setSuccess(null, array('success' => $success, 'error' => $error));
    }
}