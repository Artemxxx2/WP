<?php

namespace SabaiApps\Directories\Component\Form\Field;

use SabaiApps\Directories\Component\Form\Form;

class DateRangePickerField extends DatePickerField
{
    public function formFieldInit($name, array &$data, Form $form)
    {
        $data['#disable_time'] = true;
        $data['#placeholder'] = __('Select date range', 'directories');
        parent::formFieldInit($name, $data, $form);
    }

    public function formFieldSubmit(&$value, array &$data, Form $form)
    {
        $convert_to_system_date = !isset($data['#auto_convert_date']) || $data['#auto_convert_date'] !== false;
        if (strlen($value)
            && ($_value = explode(',', trim($value)))
            && is_array($_value)
            && isset($_value[0])
            && isset($_value[1])
            && false !== ($_value[0] = $this->_application->Form_Validate_date($_value[0], $data, $form, $convert_to_system_date))
            && false !== ($_value[1] = $this->_application->Form_Validate_date($_value[1], $data, $form, $convert_to_system_date))
        ) {
            $value = $_value;
            if ($_value[0] > $_value[1]) {
                $form->setError(__('Start date may not be later than end date.', 'directories'), $data);
            }
            return;
        }
        $value = null;
    }

    protected function _getCalendarOptions(array $data)
    {
        $options = parent::_getCalendarOptions($data);
        $options['settings']['selection']['day'] = 'multiple-ranged';
        if (!empty($data['#calendar_months'])) {
            if ($data['#calendar_months'] > 1) { // months option can only be 2 or up
                $options['type'] = 'multiple';
                $options['months'] = $data['#calendar_months'];
            }
        } else {
            $options['type'] = 'multiple';
            $options['months'] = 3;
        }
        if (!empty($data['#default_value'])) {
            if (!is_array($data['#default_value'])) {
                $_default_values = explode(',', trim($data['#default_value']));
                if (!$_default_values
                    || !isset($_default_values[0])
                    || !isset($_default_values[1])
                    || $data['#empty_value'] === $_default_values[0]
                    || $data['#empty_value'] === $_default_values[1]
                ) {
                    unset($data['#default_value']);
                } else {
                    $data['#default_value'] = [$_default_values[0], $_default_values[1]];
                }
            }
            if (!empty($data['#default_value'])) {
                foreach (array_keys($data['#default_value']) as $key) {
                    if (is_int($data['#default_value'][$key])) {
                        $data['#default_value'][$key] = date('Y-m-d', $data['#default_value'][$key]);
                    }
                }
                $options['settings']['selected']['dates'] = [$data['#default_value'][0] . ',' . $data['#default_value'][1]];
                $first_date_parts = explode('-', $data['#default_value'][0]);
                $options['settings']['selected']['year'] = $first_date_parts[0];
                $options['settings']['selected']['month'] = $first_date_parts[1] - 1;
            }
        }

        return $options;
    }
}
