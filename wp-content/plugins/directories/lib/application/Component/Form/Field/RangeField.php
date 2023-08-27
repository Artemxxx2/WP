<?php
namespace SabaiApps\Directories\Component\Form\Field;

use SabaiApps\Directories\Component\Form\Form;

class RangeField extends SliderField
{
    public function formFieldInit($name, array &$data, Form $form)
    {        
        $data['#slider_type'] = 'double';
        parent::formFieldInit($name, $data, $form);
    }

    public function formFieldSubmit(&$value, array &$data, Form $form)
    {
        $value = explode(';', $value);
        $value = array(
            'min' => isset($value[0]) ? (string)$value[0] : '',
            'max' => isset($value[1]) ? (string)$value[1] : '',
        );
        
        if (!strlen($value['min']) && !strlen($value['max'])) {
            if ($form->isFieldRequired($data)) {
                $form->setError(isset($data['#required_error_message']) ? $data['#required_error_message'] : __('Please fill out this field.', 'directories'), $data);
            }
            $value = null;
            return;
        }
        
        if (strlen($value['min'])) {
            $min = $value['min'];
        } else {
            $min = $data['#min_value'];
            unset($value['min']);
        }
        if (strlen($value['max'])) {
            $max = $value['max'];
        } else {
            $max = $data['#max_value'];
            unset($value['max']);
        }
        if ($max < $min
            || $max > $data['#max_value']
            || $min < $data['#min_value']
        ) {
            $form->setError(sprintf(__('The input range must be between %s and %s.'), $data['#min_value'], $data['#max_value']), $data);
        }
    }
}