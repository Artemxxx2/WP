<?php
namespace SabaiApps\Directories\Component\Form\Field;

use SabaiApps\Directories\Component\Form\Form;

class SelectHierarchicalField extends FieldsetField
{
    protected static $_count = 0;
    
    public function formFieldInit($name, array &$data, Form $form)
    {
        if (isset($data['#default_value'])) {
            $data['#default_value'] = (array)$data['#default_value'];
        }
        $data += array(
            '#group' => true,
        );
        ++self::$_count;
        if (!isset($data['#id'])) {
            $data['#id'] = 'drts-form-type-selecthierarchical-' . self::$_count;
        }
        $data['#attributes']['data-count'] = self::$_count;
        $data['#children'][0][0] = array(
            '#type' => 'select',
            '#weight' => 0,
            '#class' => 'drts-form-field-selecthierarchical-0',
            '#options' => $data['#options'],
            '#options_disabled' => isset($data['#options_disabled']) ? $data['#options_disabled'] : [],
            '#options_hidden' => isset($data['#options_hidden']) ? $data['#options_hidden'] : [],
            '#multiple' => false,
            '#attributes' => array(
                'data-default-value' => isset($data['#default_value'][0]) ? $data['#default_value'][0] : '',
            ),
            '#default_value' => isset($data['#default_value'][0]) ? $data['#default_value'][0] : null,
            '#select2' => empty($data['#no_fancy']),
            '#empty_value' => isset($data['#empty_value']) ? $data['#empty_value'] : null,
        );
        if (!isset($data['#max_depth'])) {
            $data['#max_depth'] = 5;
        }
        if ($max_depth = $data['#max_depth'] - 1) {
            for ($i = 1; $i <= $max_depth; $i++) {
                $options = ['' => isset($data['#options']['']) ? $data['#options'][''] : ''];
                if (isset($data['#child_options'][$i - 1])) $options += $data['#child_options'][$i - 1];
                $data['#children'][0][$i] = array(
                    '#type' => 'select',
                    '#multiple' => false,
                    '#hidden' => true,
                    '#class' => 'drts-form-field-selecthierarchical-' . $i,
                    '#attributes' => array(
                        'data-load-url' => $data['#load_options_url'],
                        'data-options-prefix' => isset($data['#load_options_prefix']) && strlen($data['#load_options_prefix']) ? str_repeat($data['#load_options_prefix'], $i) . ' ' : '',
                        'data-default-value' => isset($data['#default_value'][$i]) ? $data['#default_value'][$i] : '',
                    ),
                    '#states' => array(
                        'load_options' => array(
                            sprintf('.drts-form-field-selecthierarchical-%d select', $i - 1) => [
                                'type' => 'selected',
                                'value' => true,
                                'container' => '#' . $data['#id'],
                                'init' => !isset($data['#child_options'][$i - 1]),
                            ],
                        ),
                    ),
                    '#options' => $options,
                    '#options_disabled' => isset($data['#options_disabled']) ? $data['#options_disabled'] : [],
                    '#states_selector' => '#' . $data['#id'] . ' .drts-form-field-selecthierarchical-' . $i,
                    '#skip_validate_option' => true,
                    '#weight' => $i,
                    '#default_value' => isset($data['#default_value'][$i]) ? $data['#default_value'][$i] : null,
                    '#select2' => empty($data['#no_fancy']),
                    '#empty_value' => isset($data['#empty_value']) ? $data['#empty_value'] : null,
                    '#required' => false,
                );
            }
        }
        
        $form->settings['#pre_render'][__CLASS__] = array($this, 'preRenderCallback');
        
        parent::formFieldInit($name, $data, $form);
    }
    
    public function formFieldSubmit(&$value, array &$data, Form $form)
    {
        parent::formFieldSubmit($value, $data, $form);

        // Remove null values which may be added by fieldset form field
        $value = array_filter($value, function($v) {
            return !is_null($v);
        });

        while (null !== $_value = array_pop($value)) {
            if ($_value !== '') {
                $value = $_value;
                return;
            }
        }
        $value = null;
    }
    
    public function preRenderCallback(Form $form)
    {        
        $this->_application->getPlatform()->addJsFile('form-field-selecthierarchical.min.js', 'drts-form-field-selecthierarchical', array('drts-form'));
    }
}