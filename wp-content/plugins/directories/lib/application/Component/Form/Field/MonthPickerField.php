<?php
namespace SabaiApps\Directories\Component\Form\Field;

use SabaiApps\Directories\Component\Form\Form;

class MonthPickerField extends AbstractDatePickerField
{
    public function formFieldInit($name, array &$data, Form $form)
    {
        $data['#placeholder'] = __('Select month', 'directories');
        parent::formFieldInit($name, $data, $form);
    }

    protected function _getCalendarOptions(array $data)
    {
        $options = parent::_getCalendarOptions($data);
        $options['type'] = 'month';
        if (!empty($data['#default_value'])) {
            if (is_int($data['#default_value'])) {
                if ($data['#default_value'] !== $data['#empty_value']) {
                    $options['settings']['selected']['dates'] = [date('Y-m-d', $data['#default_value'])];
                }
            } elseif (is_string($data['#default_value'])) {
                // only date
                $options['settings']['selected']['dates'] = [$data['#default_value']];
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
