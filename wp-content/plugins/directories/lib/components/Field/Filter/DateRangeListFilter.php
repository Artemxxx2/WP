<?php
namespace SabaiApps\Directories\Component\Field\Filter;

use SabaiApps\Directories\Component\Field\IField;
use SabaiApps\Directories\Component\Field\Query;
use SabaiApps\Directories\Application;

class DateRangeListFilter extends AbstractOptionFilter
{
    protected $_minMaxSeparator = ',';

    public function __construct(Application $application, $name)
    {
        parent::__construct($application, $name);
        $this->_label = __('Date range list', 'directories');
        $this->_fieldTypes = ['date'];
        $this->_defaultSettings = [
            'ranges' => [
                'options' => null,
                'custom_ranges' => null,
            ],
            'form_type' => 'radios',
        ];
    }

    public function fieldFilterSettingsForm(IField $field, array $settings, array $parents = [])
    {
        $form = parent::fieldFilterSettingsForm($field, $settings, $parents) + [
            'ranges' => [
                '#weight' => 2,
                'options' => [
                    '#type' => 'sortablecheckboxes',
                    '#title' => __('Date ranges', 'directories'),
                    '#default_value' => isset($settings['ranges']['options']) ? $settings['ranges']['options'] : null,
                    '#options' => $this->_getDateRangeOptions(),
                    '#columns' => 3,
                ],
                'custom' => [
                    '#type' => 'checkbox',
                    '#title' => __('Custom', 'directories'),
                    '#default_value' => !empty($settings['ranges']['custom']),
                    '#switch' => false,
                ],
                'custom_ranges' => [
                    '#type' => 'rangelist',
                    '#default_value' => $settings['ranges']['custom_ranges'],
                    '#input_type' => 'text',
                    '#min_title' => _x('From', 'date', 'directories'),
                    '#max_title' => _x('To', 'date', 'directories'),
                    '#states' => [
                        'visible' => [
                            sprintf('input[name="%s[]"]', $this->_application->Form_FieldName(array_merge($parents, ['ranges', 'custom']))) => ['type' => 'checked', 'value' => true],
                        ],
                    ],
                    '#description' => __('From and To values must be recognizable by the PHP strtotime function.', 'directories'),
                ],
            ],
        ];
        if ($other_date_fields = $this->_application->Entity_Field_options($field->getBundleName(), ['type' => 'date', 'exclude' => [$field->getFieldName()], 'empty_value' => ''])) {
            $form['end_date_field'] = [
                '#type' => 'select',
                '#title' => __('End date field', 'directories'),
                '#description' => __('Select another field to use as end date.', 'directories'),
                '#default_value' => $settings['end_date_field'],
                '#options' => $other_date_fields,
            ];
        }

        return $form;
    }

    protected function _getOptions(IField $field, array $settings, &$noEscape = false)
    {
        $options_available = $this->_getDateRangeOptions();
        $options = [];
        foreach ($settings['ranges']['options'] as $key) {
            if (isset($options_available[$key])) {
                $options[$key] = $options_available[$key];
            }
        }
        if (!empty($settings['ranges']['custom'])
            && !empty($settings['ranges']['custom_ranges'])
        ) {
            foreach ($settings['ranges']['custom_ranges'] as $range) {
                $key = $range['min'] . $this->_minMaxSeparator . $range['max'];
                $options[$key] = $range['label'];
            }
        }

        return $options;
    }

    protected function _getFacetOptions(IField $field, array $settings)
    {
        $ranges = $this->_getDateRangeValues($settings['ranges']['options']);

        if (!empty($settings['ranges']['custom'])
            && !empty($settings['ranges']['custom_ranges'])
        ) {
            foreach ($settings['ranges']['custom_ranges'] as $range) {
                if (strlen($range['min'])) {
                    if (!$min = strtotime($range['min'])) continue;
                } else {
                    $min = '';
                }
                if (strlen($range['max'])) {
                    if (!$max = strtotime($range['max'])) continue;
                } else {
                    $max = '';
                }
                if ($min && $max && $min > $max) continue;

                $key = $range['min'] . $this->_minMaxSeparator . $range['max'];
                $ranges[$key] = ['min' => $min, 'max' => $max];
            }
        }

        return [
            'facet_type' => 'range',
            'column' => $this->_valueColumn,
            'ranges' => $ranges,
        ];
    }

    public function fieldFilterDoFilter(Query $query, IField $field, array $settings, $value, array &$sorts)
    {
        if (!$ranges = $this->_getDateRangeValues($value)) return;

        if (empty($settings['end_date_field'])
            || (!$end_date_field = $this->_application->Entity_Field($field->getBundleName(), $settings['end_date_field']))
        ) {
            $end_date_field = $field;
        }

        if ($settings['type'] !== 'checkboxes'
            || count($ranges) === 1
        ) {
            $range = array_shift($ranges);
            if (isset($range['min'])) {
                $query->fieldIsOrGreaterThan($end_date_field, $range['min']);
            }
            if (isset($range['max'])) {
                $query->fieldIsSmallerThan($field, $range['max']);
            }
            return;
        }

        if ($settings['andor'] === 'OR') {
            $query->startCriteriaGroup('OR');
            foreach ($ranges as $range) {
                $query->startCriteriaGroup();
                if (isset($range['min'])) {
                    $query->fieldIsOrGreaterThan($end_date_field, $range['min']);
                }
                if (isset($range['max'])) {
                    $query->fieldIsOrSmallerThan($field, $range['max']);
                }
                $query->finishCriteriaGroup();
            }
            $query->finishCriteriaGroup();
        } else {
            foreach ($ranges as $range) {
                if (isset($range['min'])) {
                    $query->fieldIsOrGreaterThan($end_date_field, $range['min']);
                }
                if (isset($range['max'])) {
                    $query->fieldIsOrSmallerThan($field, $range['max']);
                }
            }
        }
    }

    protected function _getDateRangeOptions()
    {
        return $this->_application->Filter(
            'field_filter_date_range_options',
            [
                'today' => __('Today', 'directories'),
                'yesterday' => __('Yesterday', 'directories'),
                'tomorrow' => __('Tomorrow', 'directories'),
                'this_week' => __('This week', 'directories'),
                'last_week' => __('Last week', 'directories'),
                'next_week' => __('Next week', 'directories'),
                'this_month' => __('This month', 'directories'),
                'last_month' => __('Last month', 'directories'),
                'next_month' => __('Next month', 'directories'),
                'this_year' => __('This year', 'directories'),
                'last_year' => __('Last year', 'directories'),
                'next_year' => __('Next year', 'directories'),
                'all_upcoming' => __('All upcoming', 'directories'),
                'all_past' => __('All past', 'directories'),
            ]
        );
    }

    protected function _getDateRangeValues($ranges)
    {
        $ret = [];
        foreach ((array)$ranges as $range) {
            switch ($range) {
                case 'today':
                    $ret[$range] = ['min' => $today = strtotime('today'), 'max' => $today + 86399];
                    break;
                case 'this_week':
                    $ret[$range] = ['min' => $this_week = strtotime('this week 00:00:00'), 'max' => $this_week + (86400 * 7) - 1];
                    break;
                case 'this_month':
                    $ret[$range] = ['min' => strtotime('first day of this month 00:00:00'), 'max' => strtotime('last day of this month 23:59:59')];
                    break;
                case 'this_year':
                    $ret[$range] = ['min' => strtotime('this year January 1st 00:00:00'), 'max' => strtotime('this year December 31st 23:59:59')];
                    break;
                case 'yesterday':
                    $ret[$range] = ['min' => $yesterday = strtotime('yesterday'), 'max' => $yesterday + 86399];
                    break;
                case 'last_week':
                    $ret[$range] = ['min' => $last_week = strtotime('last week 00:00:00'), 'max' => $last_week + (86400 * 7) - 1];
                    break;
                case 'last_month':
                    $ret[$range] = ['min' => strtotime('first day of last month 00:00:00'), 'max' => strtotime('last day of last month 23:59:59')];
                    break;
                case 'last_year':
                    $ret[$range] = ['min' => strtotime('last year January 1st 00:00:00'), 'max' => strtotime('last year December 31st 23:59:59')];
                    break;
                case 'tomorrow':
                    $ret[$range] = ['min' => $tomorrow = strtotime('tomorrow'), 'max' => $tomorrow + 86399];
                    break;
                case 'next_week':
                    $ret[$range] = ['min' => $next_week = strtotime('next week 00:00:00'), 'max' => $next_week + (86400 * 7) - 1];
                    break;
                case 'next_month':
                    $ret[$range] = ['min' => strtotime('first day of next month 00:00:00'), 'max' => strtotime('last day of next month 23:59:59')];
                    break;
                case 'next_year':
                    $ret[$range] = ['min' => strtotime('next year January 1st 00:00:00'), 'max' => strtotime('next year December 31st 23:59:59')];
                    break;
                case 'all_upcoming':
                    $ret[$range] = ['min' => time(), 'max' => null];
                    break;
                case 'all_past':
                    $ret[$range] = ['min' => null, 'max' => time()];
                    break;
                default:
                    if ($_range = explode($this->_minMaxSeparator, $range)) {
                        $ret[$range] = ['min' => strlen($_range[0]) ? strtotime($_range[0]) : null, 'max' => strlen($_range[1]) ? strtotime($_range[1]) : null];
                    }
            }
        }
        return $ret;
    }
}
