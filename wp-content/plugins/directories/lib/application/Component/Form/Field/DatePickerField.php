<?php
namespace SabaiApps\Directories\Component\Form\Field;

use SabaiApps\Directories\Component\Form\Form;

class DatePickerField extends AbstractDatePickerField
{
    public function formFieldInit($name, array &$data, Form $form)
    {
        $data['#disable_time'] = isset($data['#disable_time']) ? $data['#disable_time'] : false;
        if (!isset($data['#default_value'])) {
            if (!empty($data['#current_date_selected'])) {
                $data['#default_value'] = $this->_application->getPlatform()->getSystemToSiteTime(time());
            }
        } else {
            if (is_int($data['#default_value'])) {
                $data['#default_value'] = $this->_application->getPlatform()->getSystemToSiteTime($data['#default_value']);
            }
        }
        parent::formFieldInit($name, $data, $form);
    }

    public function formFieldSubmit(&$value, array &$data, Form $form)
    {
        $convert_to_system_date = !isset($data['#auto_convert_date']) || $data['#auto_convert_date'] !== false;
        if (false !== $validated = $this->_application->Form_Validate_date($value, $data, $form, $convert_to_system_date)) {
            $value = $validated;
            return;
        }
        $value = null;
    }

    protected function _getCalendarOptions(array $data)
    {
        $options = parent::_getCalendarOptions($data);
        if (!empty($data['#multiple'])) {
            $options['settings']['selection']['day'] = 'multiple';
        }
        if (!empty($data['#default_value'])) {
            $value = is_string($data['#default_value']) ? strtotime($data['#default_value']) : $data['#default_value'];
            if (is_int($value)
                && $value !== $data['#empty_value']
            ) {
                $options['settings']['selected']['dates'] = [date('Y-m-d', $value)];
                $options['settings']['selected']['time'] = empty($data['#time_12hr']) ? date('H:i', $value) : date('h:i A', $value);
            }
            if (!empty($options['settings']['selected']['dates'])) {
                $first_date_parts = explode('-', $options['settings']['selected']['dates'][0]);
                $options['settings']['selected']['year'] = $first_date_parts[0];
                $options['settings']['selected']['month'] = $first_date_parts[1] - 1;
            }
        }

        return $options;
    }
}
