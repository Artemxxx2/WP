<?php
namespace SabaiApps\Directories\Component\Field\Filter;

use SabaiApps\Directories\Component\Field\IField;
use SabaiApps\Directories\Component\Field\Query;
use SabaiApps\Directories\Component\Entity;

class TimeFilter extends AbstractFilter
{
    protected function _fieldFilterInfo()
    {
        return array(
            'field_types' => array('time', 'directory_opening_hours'),
            'default_settings' => [
                'no_fancy' => true,
            ],
        );
    }

    public function fieldFilterSettingsForm(IField $field, array $settings, array $parents = [])
    {
        return [
            'no_fancy' => [
                '#type' => 'checkbox',
                '#title' => __('Disable fancy dropdown', 'directories'),
                '#default_value' => !empty($settings['no_fancy']),
            ],
        ];
    }

    protected function _getTimeOptions($interval = 300, $format = 'H:i')
    {
        $range = range(0, 86400 - $interval, $interval);
        return array_map(function ($i) use ($format) { return date($format, $i); }, array_combine($range, $range));
    }

    public function fieldFilterForm(IField $field, $filterName, array $settings, $request = null, Entity\Type\Query $query = null, array $current = null, $autoSubmit = true, array $parents = [])
    {
        $field_settings = $field->getFieldSettings();
        $ret = [
            '#entity_filter_form_type' => 'select',
            '#group' => true,
            'time' => [
                '#type' => 'select',
                '#select2' => empty($settings['no_fancy']),
                '#options' => ['' => __('— Select —', 'directories')] + $this->_getTimeOptions(1800),
                '#select2_placeholder' => __('Time', 'directories'),
                '#empty_value' => '',
                '#class' => 'drts-view-filter-ignore',
                '#select2_allow_clear' => false,
                '#weight' => 2,
            ],
            'button' => array(
                '#prefix' => '<div class="drts-col-2 drts-view-filter-trigger-btn">',
                '#suffix' => '</div></div>',
                '#type' => 'markup',
                '#markup' => '<button type="button" class="' . DRTS_BS_PREFIX . 'btn ' . DRTS_BS_PREFIX . 'btn-link ' . DRTS_BS_PREFIX . 'btn-block drts-view-filter-trigger">' .
                    '<i class="fas fa-fw fa-search"></i></button>',
                '#weight' => 3,
            ),
        ];
        if (!empty($field_settings['enable_day'])
            || $field->getFieldType() === 'directory_opening_hours'
        ) {
            $ret['day'] = [
                '#prefix' => '<div class="drts-row drts-gutter-none"><div class="drts-col-10 drts-view-filter-trigger-main"><div class="drts-row drts-gutter-xs"><div class="drts-col-6">',
                '#suffix' => '</div>',
                '#type' => 'select',
                '#select2' => empty($settings['no_fancy']),
                '#options' => ['' => __('— Select —', 'directories')] + $this->_application->Days(),
                '#select2_placeholder' => _x('Day', 'day of week', 'directories'),
                '#empty_value' => '',
                '#class' => 'drts-view-filter-ignore',
                '#select2_allow_clear' => false,
                '#weight' => 1,
            ];
            $ret['time']['#prefix'] = '<div class="drts-col-6">';
            $ret['time']['#suffix'] = '</div></div></div>';
        } else {
            $ret['time']['#prefix'] = '<div class="drts-row drts-gutter-none">'
                . '<div class="drts-col-10 drts-view-filter-trigger-main">';
            $ret['time']['#suffix'] = '</div>';
        }
        return $ret;
    }

    public function fieldFilterIsFilterable(IField $field, array $settings, &$value, array $requests = null)
    {
        $field_settings = $field->getFieldSettings();
        if (empty($field_settings['enable_day'])
            && $field->getFieldType() !== 'directory_opening_hours'
        ) {
            return strlen($value['time']) > 0;
        }

        return !empty($value['day']) || strlen($value['time']) > 0;
    }

    public function fieldFilterDoFilter(Query $query, IField $field, array $settings, $value, array &$sorts)
    {
        $field_settings = $field->getFieldSettings();
        if (strlen($value['time'])) {
            if (empty($field_settings['enable_end'])
                && $field->getFieldType() !== 'directory_opening_hours'
            ) {
                $query->fieldIs($field, $value['time'], 'start');
            } else {
                $query->fieldIsOrSmallerThan($field, $value['time'], 'start')
                    ->fieldIsOrGreaterThan($field, $value['time'], 'end');
            }
        }
        if ((!empty($field_settings['enable_day']) || $field->getFieldType() === 'directory_opening_hours')
            && !empty($value['day'])
        ) {
            $query->fieldIs($field, $value['day'], 'day');
        }
    }

    public function fieldFilterLabels(IField $field, array $settings, $value, $form, $defaultLabel)
    {
        $label = [];
        if (!empty($value['day'])) {
            $label[] = $this->_application->Days($value['day']);
        }
        if (strlen($value['time'])) {
            $label[] = $this->_application->System_Date_time($value['time']);
        }

        return array('' => $this->_application->H(empty($label) ? $defaultLabel : $defaultLabel . ': ' . implode(' ', $label)));
    }
}
