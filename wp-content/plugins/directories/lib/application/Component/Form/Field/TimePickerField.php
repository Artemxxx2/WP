<?php
namespace SabaiApps\Directories\Component\Form\Field;

use SabaiApps\Directories\Component\Form\Form;

class TimePickerField extends AbstractField
{
    const WEEKDAYS = 8, WEEKEND = 9, ALL_WEEK = 10;

    public function formFieldInit($name, array &$data, Form $form)
    {
        if (!isset($data['#id'])) {
            $data['#id'] = $form->getFieldId($name);
        }
        if (!isset($data['#default_value'])) {
            if (!empty($data['#current_time_selected'])) {
                $current_time = $this->_application->getPlatform()->getSystemToSiteTime(time());
                $data['#default_value'] = [
                    'start' => $current_time,
                    'end' => null,
                    'day' => date('w', $current_time),
                ];
            }
        } else {
            foreach (['day', 'start', 'end'] as $key) {
                if (isset($data['#default_value'][$key])
                    && is_numeric($data['#default_value'][$key])
                ) {
                    $data['#default_value'][$key] = intval($data['#default_value'][$key]);
                } else {
                    $data['#default_value'][$key] = null;
                }
            }
        }

        $form->settings['#pre_render'][__CLASS__] = [[$this, 'preRenderCallback'], [empty($data['#disable_day']) && empty($data['#allow_empty_day'])]];
    }

    public function formFieldSubmit(&$value, array &$data, Form $form)
    {
        if (!is_array($value)) {
            if ($form->isFieldRequired($data)) {
                $form->setError(isset($data['#required_error_message']) ? $data['#required_error_message'] : __('Please pick a time.', 'directories'), $data);
            }
            $value = null;
            return;
        }

        if (($all_day_options = $this->_getAllDayOptions($data))
            && !empty($value['all_day'])
            && in_array($value['all_day'], array_keys($all_day_options))
        ) {
            if (empty($data['#disable_day'])) {
                if (empty($data['#allow_empty_day'])
                    && empty($value['day'])
                ) {
                    $form->setError(__('Please select a day of week.', 'directories'), $data);
                    return;
                }
            }
            $value['start'] = 0;
            $value['end'] = $value['all_day'] == 1 ? 86400 : 0;
            return;
        }
        $value['all_day'] = '';

        if (!isset($value['start'])
            || !strlen($value['start'] = trim($value['start']))
        ) {
            if ($form->isFieldRequired($data)
                || (empty($data['#disable_end']) && isset($value['end']) && strlen($value['end']))  // end time selected
            ) {
                $form->setError(isset($data['#required_error_message']) ? $data['#required_error_message'] : __('Please pick a time.', 'directories'), $data);
                return;
            }
            // no start/end value
            if (empty($data['#disable_day'])
                && !empty($value['day'])
            ) { // day selected
                if (empty($data['#allow_day_only'])) {
                    $form->setError(isset($data['#required_error_message']) ? $data['#required_error_message'] : __('Please pick a time.', 'directories'), $data);
                }
                return;
            }
            // no start/end/day value
            $value = null;
        } else {
            if (empty($data['#disable_day'])) {
                if (empty($data['#allow_empty_day'])
                    && empty($value['day'])
                ) {
                    $form->setError(__('Please select a day of week.', 'directories'), $data);
                    return;
                }
            }
            if (empty($data['#disable_end'])
                && (!isset($value['end']) || !strlen($value['end'] = trim($value['end'])))
            ) {
                $form->setError(__('Please select an end time.', 'directories'), $data);
            }
        }
    }

    public function formFieldRender(array &$data, Form $form)
    {
        if (isset($data['#date_locale'])) {
            $data['#data']['date-locale'] = $data['#date_locale'];
        }
        $gutter = isset($data['#gutter_size']) ? ' drts-gutter-' . $data['#gutter_size'] : '';
        $html = array('<div class="drts-row' . $gutter . '">');
        if (empty($data['#disable_day'])) {
            if (empty($data['#disable_end'])) {
                if ($all_day_field = $this->_getAllDayField($data)) {
                    $html[] = sprintf(
                        '<div class="drts-form-timepicker-day drts-col-sm-6 drts-col-md-3">%s</div>'
                        . '<div class="drts-form-timepicker-all_day drts-col-sm-6 drts-col-md-3">%s</div>'
                        . '<div class="drts-form-timepicker-start drts-col-sm-6 drts-col-md-3">%s</div>'
                        . '<div class="drts-form-timepicker-end drts-col-sm-6 drts-col-md-3">%s</div>',
                        $this->_getDays($data),
                        $all_day_field,
                        $this->_getTimeField($data, 'start'),
                        $this->_getTimeField($data, 'end')
                    );
                } else {
                    $html[] = sprintf(
                        '<div class="drts-form-timepicker-day drts-col-md-6">%s</div>'
                        . '<div class="drts-form-timepicker-start drts-col-sm-6 drts-col-md-3">%s</div>'
                        . '<div class="drts-form-timepicker-end drts-col-sm-6 drts-col-md-3">%s</div>',
                        $this->_getDays($data),
                        $this->_getTimeField($data, 'start'),
                        $this->_getTimeField($data, 'end')
                    );
                }
            } else {
                if ($all_day_field = $this->_getAllDayField($data)) {
                    $html[] = sprintf(
                        '<div class="drts-form-timepicker-day drts-col-sm-6 drts-col-md-3">%s</div>'
                        . '<div class="drts-form-timepicker-all_day drts-col-sm-6 drts-col-md-3">%s</div>'
                        . '<div class="drts-form-timepicker-start drts-col-sm-6">%s</div>',
                        $this->_getDays($data),
                        $all_day_field,
                        $this->_getTimeField($data, 'start')
                    );
                } else {
                    $html[] = sprintf(
                        '<div class="drts-form-timepicker-day drts-col-sm-6">%s</div>'
                        . '<div class="drts-form-timepicker-start drts-col-sm-6">%s</div>',
                        $this->_getDays($data),
                        $this->_getTimeField($data, 'start')
                    );
                }
            }
        } else {
            if (empty($data['#disable_end'])) {
                if (!empty($data['#allow_empty_day'])
                    && ($all_day_field = $this->_getAllDayField($data))
                ) {
                    $html[] = sprintf(
                        '<div class="drts-form-timepicker-all_day drts-col-md-6">%s</div>'
                        . '<div class="drts-form-timepicker-start drts-col-sm-6 drts-col-md-3">%s</div>'
                        . '<div class="drts-form-timepicker-end drts-col-sm-6 drts-col-md-3">%s</div>',
                        $all_day_field,
                        $this->_getTimeField($data, 'start'),
                        $this->_getTimeField($data, 'end')
                    );
                } else {
                    $html[] = sprintf(
                        '<div class="drts-form-timepicker-start drts-col-sm-6">%s</div>'
                        . '<div class="drts-form-timepicker-end drts-col-sm-6">%s</div>',
                        $this->_getTimeField($data, 'start'),
                        $this->_getTimeField($data, 'end')
                    );
                }
            } else {
                if (!empty($data['#allow_empty_day'])
                    && ($all_day_field = $this->_getAllDayField($data))
                ) {
                    $html[] = sprintf(
                        '<div class="drts-form-timepicker-all_day drts-col-md-6">%s</div>'
                            . '<div class="drts-form-timepicker-start drts-col-md-6">%s</div>',
                        $all_day_field,
                        $this->_getTimeField($data, 'start')
                    );
                } else {
                    $html[] = sprintf(
                        '<div class="drts-form-timepicker-start drts-col-md-6">%s</div>',
                        $this->_getTimeField($data, 'start')
                    );
                }
            }
        }
        $html[] = '</div>';
        $this->_render(implode(PHP_EOL, $html), $data, $form);
    }

    protected function _getAllDayOptions(array $data)
    {
        if (empty($data['#all_day_options'])) return;

        $options = [
            '' => __('Enter hours', 'directories'),
            1 => __('All day', 'directories'),
        ];
        if (is_array($data['#all_day_options'])) {
            foreach ($data['#all_day_options'] as $value => $label) {
                $options[$value] = $label;
            }
        }
        return $options;
    }

    protected function _getAllDayField(array $data)
    {
        if (!$options = $this->_getAllDayOptions($data)) return;

        $ret = [sprintf(
            '<select class="%sform-control" name="%s[all_day]"%s>',
            DRTS_BS_PREFIX,
            $data['#name'],
            $data['#disabled'] ? ' disabled="disabled"' : ''
        )];
        foreach ($options as $key => $label) {
            $ret[] = sprintf(
                '<option value="%s"%s>%s</option>',
                $key,
                isset($data['#default_value']['all_day']) && $data['#default_value']['all_day'] == $key ? ' selected="selected"' : '',
                $this->_application->H($label)
            );
        }
        $ret[] = '</select>';

        return implode(PHP_EOL, $ret);
    }

    protected function _getDays(array $data)
    {
        $ret = array(sprintf(
            '<select class="%sform-control" name="%s[day]"%s%s>',
            DRTS_BS_PREFIX,
            $data['#name'],
            $data['#disabled'] ? ' disabled="disabled"' : '',
            empty($data['#allow_empty_day']) ? '' : ' data-allow-empty="1"'
        ));
        $options = ['' => __('— Select —', 'directories')] + $this->_application->Days();
        if (!empty($data['#enable_day_bulk'])) {
            $options += [
                self::WEEKDAYS => __('Monday', 'directories') . ' - ' . __('Friday', 'directories'),
                self::WEEKEND => __('Saturday', 'directories') . ' - ' . __('Sunday', 'directories'),
                self::ALL_WEEK => __('All week', 'directories'),
            ];
        }

        foreach ($options as $key => $day) {
            $ret[] = sprintf(
                '<option value="%s"%s>%s</option>',
                $key,
                isset($data['#default_value']['day']) && $data['#default_value']['day'] === $key ? ' selected="selected"' : '',
                $this->_application->H($day)
            );
        }
        $ret[] = '</select>';

        return implode(PHP_EOL, $ret);
    }

    protected function _getTimeField(array $data, $name)
    {
        $value = null;
        if (isset($data['#default_value'][$name])) {
            $value = $data['#default_value'][$name] % 86400;
        }
        if (!empty($data['#time_format'])) {
            if ($data['#time_format'] === 24) {
                $time_format = 'H:i';
            } else {
                $time_format = 'g:i A';
            }
        } else {
            $time_format = $this->_application->getPlatform()->getTimeFormat();
        }
        $time_step = isset($data['#time_step']) ? $data['#time_step'] : 15;
        $options = [];
        $is_selected = false;
        for ($i = 0; $i < 24; ++$i) {
            for ($j = 0; $j < 60; $j += $time_step) {
                $option_value = $i * 3600 + $j * 60;
                if (!$is_selected
                    && $value === $option_value
                ) {
                    $selected = ' selected="selected"';
                    $is_selected = true;
                } else {
                    $selected = '';
                }
                $options[$option_value] = '<option value="' . $option_value . '"' . $selected . '>' . date($time_format, mktime($i, $j, 0)) . '</option>';
            }
        }
        if (isset($value)
            && !$is_selected
        ) {
            if ($value % 60) $value -= $value % 60;
            $options[$value] = '<option value="' . $value . '" selected="selected">' . date($time_format, mktime(intval($value / 3600), intval(($value % 3600) / 60), 0)) . '</option>';
        }
        ksort($options);

        return sprintf(
            '<select class="%1$sform-control" name="%2$s[%3$s]"%4$s><option value="">--</option>%5$s</select>',
            DRTS_BS_PREFIX,
            $data['#name'],
            $name,
            $data['#disabled'] ? ' disabled="disabled"' : '',
            implode(PHP_EOL, $options)
        );
    }

    public function preRenderCallback(Form $form, $requireDay)
    {
        $this->_application->Form_Scripts_time();

        $form->settings['#js_ready'][] = sprintf(
            '(function() {
    $("#%s").find(".drts-form-type-timepicker").each(function(){
        DRTS.Form.field.timepicker($(this), {requireDay: %s}); 
    });
})();',
            $form->settings['#id'],
            empty($requireDay) ? 'false' : 'true'
        );
    }
}
