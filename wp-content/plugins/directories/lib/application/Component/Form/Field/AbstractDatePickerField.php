<?php

namespace SabaiApps\Directories\Component\Form\Field;

use SabaiApps\Directories\Component\Form\Form;

abstract class AbstractDatePickerField extends AbstractField
{
    protected static $_locales = [];

    public function formFieldInit($name, array &$data, Form $form)
    {
        if (!isset($data['#id'])) {
            $data['#id'] = $form->getFieldId($name);
        }

        if (!array_key_exists('#empty_value', $data)) {
            $data['#empty_value'] = null;
        }
        if (!isset($data['#default_value'])) {
            $data['#default_value'] = null;
        }

        // Define min/max date
        if (isset($data['#min_date']) && !is_int($data['#min_date'])) {
            unset($data['#min_date']);
        }
        if (isset($data['#max_date'])) {
            if (!is_int($data['#max_date'])
                || (isset($data['#min_date']) && $data['#max_date'] < $data['#min_date'])
            ) {
                unset($data['#max_date']);
            }
        }

        if (!isset($data['#date_locale'])) {
            $data['#date_locale'] = $this->_application->Form_Scripts_dateLocale();
        }
        if (isset($data['#date_locale'])) {
            if (!$this->_application->Form_Scripts_isValidDateLocale($data['#date_locale'])) {
                $data['#date_locale'] = null;
            } else {
                self::$_locales[$form->settings['#id']][] = $data['#date_locale'];
            }
        }

        $form->settings['#pre_render'][__CLASS__] = [$this, 'preRenderCallback'];
    }

    public function formFieldRender(array &$data, Form $form)
    {
        $data['#attributes']['data-calendar-options'] = json_encode($this->_getCalendarOptions($data));
        $values = empty($settings['selected']['dates']) ? [] : $settings['selected']['dates'];
        $html = sprintf(
            '<div class="drts-form-datepicker-inputs" id="%1$s-inputs">
    <div class="drts-row">
        <div class="drts-col-md-%3$d drts-view-filter-ignore">%2$s</div>
    </div>
    <div class="drts-form-datepicker-calendar" style="display:none;"></div>
</div>',
            $this->_application->H($data['#id']),
            $this->_getDateInput($data),
            isset($data['#col']) ? $data['#col'] : 6
        );
        $this->_render($html, $data, $form);
    }

    protected function _getDateInput(array $data)
    {
        if (!isset($data['#attributes']['placeholder'])) {
            $data['#attributes']['placeholder'] = isset($data['#placeholder']) ? $data['#placeholder'] : __('Select date', 'directories');
        }
        $add_clear = !isset($data['#add_clear']) || $data['#add_clear'];
        return sprintf(
            '<input readonly type="text" size="8" class="%2$sform-control drts-form-datepicker-date%5$s"%3$s />'
            . '<input type="hidden" name="%1$s" class="drts-form-datepicker-date-val" />%4$s',
            $data['#name'],
            DRTS_BS_PREFIX,
            $this->_application->Attr($data['#attributes']),
            $add_clear ? '<i class="drts-clear fas fa-times-circle" data-clear></i>' : '',
            $add_clear ? ' drts-form-type-textfield-with-clear' : ''
        );
    }

    protected function _getCalendarOptions(array $data)
    {
        return [
            'type' => 'default',
            'date' => [
                'min' => empty($data['#min_date']) ? null : date('Y-m-d', $data['#min_date']),
                'max' => empty($data['#max_date']) ? null : date('Y-m-d', $data['#max_date']),
            ],
            'settings' => [
                'lang' => $data['#date_locale'],
                'selection' => [
                    'time' => empty($data['#disable_time']) ? (empty($data['#time_12hr']) ? 24 : true) : false,
                ],
                'selected' => [
                    'year' => empty($data['#default_year']) ? null : $data['#default_year'],
                ],
                'visibility' => [
                    'theme' => 'light',
                ],
            ],
        ];
    }

    public function preRenderCallback($form)
    {
        $this->_application->Form_Scripts_date(
            isset(self::$_locales[$form->settings['#id']]) ? self::$_locales[$form->settings['#id']] : null // locale
        );
        $form->settings['#js_ready'][] = sprintf(
            '(function() {
    $("#%s").find(".drts-form-datepicker-inputs").each(function(){
        DRTS.Form.field.datepicker($(this)); 
    });
})();',
            $form->settings['#id']
        );
    }
}
