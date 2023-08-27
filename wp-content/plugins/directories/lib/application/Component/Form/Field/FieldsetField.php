<?php
namespace SabaiApps\Directories\Component\Form\Field;

use SabaiApps\Directories\Component\Form\Form;
use SabaiApps\Directories\Exception;

class FieldsetField extends AbstractField
{
    public function formFieldInit($name, array &$data, Form $form)
    {
        if (isset($form->settings['#tree_allow_override']) && !$form->settings['#tree_allow_override']) {
            // #tree setting not allowed to be overridden for the whole form
            $data['#tree_allow_override'] = false;
            $data['#tree'] = $form->settings['#tree'];
        } elseif (!empty($form->settings['#tree']) && !isset($data['#tree'])) {
            // inherit form #tree setting
            $data['#tree'] = true;
        }

        foreach (array_keys($data['#children']) as $weight) {
            if (!is_int($weight)) continue;
            
            foreach (array_keys($data['#children'][$weight]) as $_key) {
                $_data =& $data['#children'][$weight][$_key];
                $_data += $form->defaultFieldSettings();
                // Make the child element required/disabled if not set as not required explicitly and the fieldset is set as required/disabled
                if (!isset($_data['#required']) && isset($data['#required'])) {
                    $_data['#required'] = $data['#required'];
                }
                if (!isset($_data['#required_error_message']) && isset($data['#required_error_message'])) {
                    $_data['#required_error_message'] = $data['#required_error_message'];
                }
                if (!isset($_data['#disabled']) && !empty($data['#disabled'])) {
                    $_data['#disabled'] = true;
                }
                // Append parent element name if #tree is true for the parent element and #tree is not set to false explicitly for the current element
                if (!empty($data['#tree']) && (!$data['#tree_allow_override'] || false !== @$_data['#tree'])) {
                    $_data['#tree'] = true;
                    $_name = sprintf('%s[%s]', $name, $_key);
                    if (!isset($_data['#value']) && isset($data['#value'][$_key])) {
                        $_data['#value'] = $data['#value'][$_key];
                    }
                    if (!isset($_data['#default_value']) && isset($data['#default_value'][$_key])) {
                        $_data['#default_value'] = $data['#default_value'][$_key];
                    }
                } else {
                    $_name = $_key;
                }
                $_data['#tree_allow_override'] = empty($data['#tree_allow_override']) ? false : !empty($_data['#tree_allow_override']);
                $form->initField($_name, $_data);
            }
        }
    }

    public function formFieldSubmit(&$value, array &$data, Form $form)
    {        
        if (!empty($data['#tree'])) {
            if (!is_array($value)) {
                $value = [];
            }
            foreach (array_keys($data['#children']) as $weight) {
                if (!is_int($weight)) continue;
            
                foreach (array_keys($data['#children'][$weight]) as $key) {
                    $_data =& $data['#children'][$weight][$key];
                    if (!empty($_data['#disabled'])
                        || in_array($_data['#type'], array('markup', 'addmore'))
                    ) {
                        unset($value[$key]);
                        continue;
                    }
                    
                    if (empty($value) || !array_key_exists($key, $value)) {
                        $value[$key] = null;
                    }

                    // Send form submit notification to the field
                    try {
                        $this->_application->Form_Fields_impl($_data['#type'], $form->settings['#build_id'])->formFieldSubmit($value[$key], $_data, $form);
                    } catch (Exception\IException $e) {
                        // Catch any application level exception that might occur and display it as a form element error.
                        $form->setError($e->getMessage(), $_data);
                    }

                    if ($form->hasError($_data['#name'])) continue;

                    // Copy the value to be used in subsequent validation steps
                    $_value =& $value[$key];

                    if (empty($form->settings['#skip_validate'])) {
                        // Process custom validations if any
                        foreach ($_data['#element_validate'] as $callback) {
                            try {
                                $this->_application->CallUserFuncArray($callback, array($form, &$_value, $_data));
                            } catch (Exception\IException $e) {
                                $form->setError($e->getMessage(), $_data);
                            }
                        }
                    }
                }
            }
            if (!empty($value) && !empty($data['#remove_empty'])) {
                $value = array_filter($value);
            }
            return;
        }      
        
        // Process child fields
        foreach (array_keys($data['#children']) as $weight) {
            if (!is_int($weight)) continue;

            foreach (array_keys($data['#children'][$weight]) as $key) {
                $_data =& $data['#children'][$weight][$key];
                if (!empty($_data['#disabled'])
                    || in_array($_data['#type'], array('markup', 'addmore'))
                ) {
                    unset($form->values[$key]);
                    continue;
                }
                
                // Since the name of element does not belongs to the group name hierarchy, we must fetch the element's value from the global scope.
                if (!isset($form->values[$key])) {
                    $form->values[$key] = null;
                }

                // Send form submit notification to the element.
                try {
                    $this->_application->Form_Fields_impl($_data['#type'], $form->settings['#build_id'])->formFieldSubmit($form->values[$key], $_data, $form);
                } catch (Exception\IException $e) {
                    // Catch exception that might occur and display it as a form element error.
                    $form->setError($e->getMessage(), $_data);
                }

                if ($form->hasError($key)) continue;

                // Copy the value to be used in subsequent validation steps
                $_value =& $form->values[$key];

                // Process custom validations if any
                foreach ($_data['#element_validate'] as $callback) {
                    try {
                        $this->_application->CallUserFuncArray($callback, array($form, &$_value, $_data));
                    } catch (Exception\IException $e) {
                        $form->setError($e->getMessage(), $_data);
                    }
                }
            }
        }
    }
    
    public function formFieldCleanup(array &$data, Form $form)
    {
        $form->cleanupChildFields($data);
    }

    public function formFieldRender(array &$data, Form $form)
    {
        $form->renderChildFields($data);
    }
}
