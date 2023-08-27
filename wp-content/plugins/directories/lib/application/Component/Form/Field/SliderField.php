<?php
namespace SabaiApps\Directories\Component\Form\Field;

use SabaiApps\Directories\Component\Form\Form;

class SliderField extends TextField
{
    public function formFieldInit($name, array &$data, Form $form)
    {        
        if (!isset($data['#id'])) {
            $data['#id'] = $form->getFieldId($name);
        }
        if (empty($data['#integer'])) {
            $data['#min_value'] = isset($data['#min_value']) && is_numeric($data['#min_value']) ? $data['#min_value'] : 0;
            $data['#max_value'] = isset($data['#max_value']) && is_numeric($data['#max_value']) ? $data['#max_value'] : 100;
            if (!isset($data['#step'])) {
                $data['#step'] = 1;
            }
        } else {
            $data['#min_value'] = isset($data['#min_value']) ? intval($data['#min_value']) : 0;
            $data['#max_value'] = isset($data['#max_value']) ? intval($data['#max_value']) : 100;
            $data['#step'] = isset($data['#step']) ? $data['#step'] : 1;
        }
        if (!isset($data['#size'])) {
            $data['#size'] = strlen($data['#max_value']) + 2;
        }
        if (isset($data['#js_ready'])) {
            settype($data['#js_ready'], 'array');
        } else {
            $data['#js_ready'] = [];
        }
        $data['#js_ready'][] = 'DRTS.Form.field.slider("#' . $data['#id'] . '");';
        unset($data['#separator']);
        
        $form->settings['#pre_render'][__CLASS__] = array($this, 'preRenderCallback');
        
        parent::formFieldInit($name, $data, $form);
    }
    
    public function formFieldSubmit(&$value, array &$data, Form $form)
    {
        parent::formFieldSubmit($value, $data, $form);
        if (!empty($data['#integer'])
            && is_numeric($value)
        ) {
            $value = intval($value);
        }
    }

    public function formFieldRender(array &$data, Form $form)
    {
        if (!empty($data['#disabled'])) {
            $data['#slider_disable'] = true;
        }
        if (isset($data['#attributes']['class'])) {
            $data['#attributes']['class'] .= ' drts-form-slider';
        } else {
            $data['#attributes']['class'] = ' drts-form-slider';
        }
        foreach (array('type', 'disable', 'max_postfix', 'force_edges', 'hide_min_max', 'grid', 'grid_snap', 'values_separator', 'from', 'to') as $key) {
            $setting = 'slider_' . $key;
            if (isset($data['#' . $setting])) {
                $value = $data['#' . $setting];
                $data['#attributes']['data-' . str_replace('_', '-', $key)] = is_array($value) ?
                    $this->_application->JsonEncode($value) :
                    (is_bool($value) ? ($value ? 'true' : 'false') : $value);
            }
        }
        $data['#attributes']['data-step'] = $data['#step'];
        $data['#attributes']['data-min'] = $data['#min_value'];
        $data['#attributes']['data-max'] = $data['#max_value'];
        $data['#attributes']['data-min-text'] = isset($data['#min_text']) ? $data['#min_text'] : '';
        $data['#attributes']['data-max-text'] = isset($data['#max_text']) ? $data['#max_text'] : '';
        if (!isset($data['#attributes']['data-grid'])) {
            //$min_max_diff = $data['#max_value'] - $data['#min_value'];
            //$data['#attributes']['data-grid'] = $min_max_diff > 20 && $min_max_diff %5 === 0;
            $data['#attributes']['data-grid'] = 'true';
        }
        if (!empty($data['#slider_values'])) {
            $data['#attributes']['data-values'] = implode(',', $data['#slider_values']);
        }
        unset($data['#min_value'], $data['#max_value']);
        if (isset($data['#field_prefix'])) {
            $data['#attributes']['data-prefix'] = $data['#field_prefix'] . ' ';
            unset($data['#field_prefix']);
        }
        if (isset($data['#field_suffix'])) {
            $data['#attributes']['data-postfix'] = ' ' . $data['#field_suffix'];
            unset($data['#field_suffix']);
        }
        if (!isset($data['#attributes']['data-input-values-separator'])) {
            $data['#attributes']['data-input-values-separator'] = ';';
        }
        if (isset($data['#default_value'])) {
            $min = $max = '';
            if (is_array($data['#default_value'])) {
                if (isset($data['#default_value']['min'])) {
                    $min = $data['#default_value']['min'];
                }
                if (isset($data['#default_value']['max'])) {
                    $max = $data['#default_value']['max'];
                }
            } else {
                $value = explode(';', $data['#default_value']);
                if (isset($value[0])) {
                    $min = $value[0];
                }
                if (isset($value[1])) {
                    $max = $value[1];
                }
            }
            if (!empty($data['#slider_values'])) {
                // Specify position in values array when using #slider_values
                if (isset($min)
                    && $min !== ''
                ) {
                    if (false === $min = array_search($min, $data['#slider_values'])) {
                        $min = '';
                    }
                }
                if (isset($max)
                    && $max !== ''
                ) {
                    if (false === $max = array_search($max, $data['#slider_values'])) {
                        $max = '';
                    }
                }
            }
            if (!isset($data['#attributes']['data-from'])) {
                $data['#attributes']['data-from'] = $min;
            }
            if (!isset($data['#attributes']['data-to'])) {
                $data['#attributes']['data-to'] = $max;
            }
            if (strlen($min) || strlen($max)) {
                $data['#default_value'] = $min . $data['#attributes']['data-input-values-separator'] . $max;
            }
        }
        if (!isset($data['#attributes']['data-force-edges'])) {
            $data['#attributes']['data-force-edges'] = 'true';
        }
        if (!isset($data['#attributes']['data-hide-min-max'])) {
            $data['#attributes']['data-hide-min-max'] = 'true';
        }
        $this->_render($this->_renderInput($data, $form), $data, $form);
    }

    public function preRenderCallback(Form $form)
    {
        $this->_application->getPlatform()->addJsFile('ion.rangeSlider.min.js', 'ion-range-slider', array('jquery'), null, true, true)
            ->addJsFile('form-field-slider.min.js', 'drts-form-field-slider', array('drts-form', 'ion-range-slider'))
            ->addCssFile('ion.rangeSlider.min.css', 'ion-range-slider', null, null, null, true);
    }
}