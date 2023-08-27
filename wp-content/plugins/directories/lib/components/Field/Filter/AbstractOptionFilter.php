<?php
namespace SabaiApps\Directories\Component\Field\Filter;

use SabaiApps\Directories\Component\Field\IField;
use SabaiApps\Directories\Component\Entity;

abstract class AbstractOptionFilter extends AbstractFilter
{
    protected $_label = null, $_fieldTypes = [], $_defaultSettings = [], $_emptyValue = '', $_valueColumn = 'value', $_prefixLabel = true, $_labelSortable = false;

    protected function _fieldFilterInfo()
    {
        return [
            'label' => $this->_label,
            'field_types' => $this->_fieldTypes,
            'default_settings' => $this->_defaultSettings + [
                'type' => 'checkboxes',
                'columns' => 1,
                'show_more' => ['num' => 10],
                'andor' => 'AND',
                'default_text' => _x('Any', 'option', 'directories'),
                'no_fancy' => true,
                'sort' => false,
                'hide_empty' => false,
                'hide_count' => false,
                'sort_by_count' => false,
            ],
            'facetable' => true,
        ];
    }

    public function fieldFilterSettingsForm(IField $field, array $settings, array $parents = [])
    {
        $form = [
            'type' => [
                '#title' => __('Form field type', 'directories'),
                '#type' => 'select',
                '#options' => [
                    'checkboxes' => __('Checkboxes', 'directories'),
                    'radios' => __('Radio buttons', 'directories'),
                    'select' => __('Select list', 'directories')
                ],
                '#default_value' => $settings['type'],
                '#weight' => 5,
            ],
            'columns' => [
                '#type' => 'select',
                '#title' => __('Number of columns', 'directories'),
                '#options' => [1 => 1, 2 => 2, 3 => 3, 4 => 4],
                '#default_value' => $settings['columns'],
                '#weight' => 10,
                '#states' => [
                    'visible' => [
                        sprintf('[name="%s[type]"]', $states_selector_prefix = $this->_application->Form_FieldName($parents)) => ['type' => 'one', 'value' => ['checkboxes', 'radios']],
                    ],
                ],
            ],
            'show_more' => [
                '#states' => [
                    'visible' => [
                        sprintf('[name="%s[type]"]', $states_selector_prefix) => ['type' => 'one', 'value' => ['checkboxes', 'radios']],
                        sprintf('[name="%s[columns]"]', $states_selector_prefix) => ['value' => 1],
                    ],
                ],
                'num' => [
                    '#type' => 'slider',
                    '#integer' => true,
                    '#min_value' => 0,
                    '#max_value' => 50,
                    '#min_text' => __('Show all', 'directories'),
                    '#title' => __('Number of options to display', 'directories'),
                    '#description' => __('If there are more options than the number specified, those options are hidden until "more" link is clicked.', 'directories'),
                    '#default_value' => $settings['show_more']['num'],
                ],
                '#weight' => 15,
            ],
            'andor' => [
                '#title' => __('Match any or all', 'directories'),
                '#type' => 'select',
                '#options' => ['OR' => __('Match any', 'directories'), 'AND' => __('Match all', 'directories')],
                '#default_value' => $settings['andor'],
                '#states' => [
                    'visible' => [
                        sprintf('[name="%s[type]"]', $states_selector_prefix) => ['value' => 'checkboxes'],
                    ],
                ],
                '#weight' => 20,
            ],
            'default_text' => [
                '#type' => 'textfield',
                '#title'=> __('Default text', 'directories'),
                '#default_value' => $settings['default_text'],
                '#placeholder' => _x('Any', 'option', 'directories'),
                '#weight' => 25,
                '#states' => [
                    'visible' => [
                        sprintf('[name="%s[type]"]', $states_selector_prefix) => ['type' => 'one', 'value' => ['select', 'radios']],
                    ],
                ],
            ],
            'no_fancy' => [
                '#type' => 'checkbox',
                '#title' => __('Disable fancy dropdown', 'directories'),
                '#default_value' => !empty($settings['no_fancy']),
                '#weight' => 30,
                '#states' => [
                    'visible' => [
                        sprintf('[name="%s[type]"]', $states_selector_prefix) => ['value' => 'select'],
                    ],
                ],
            ],
        ];
        if ($this->_labelSortable) {
            $form['sort'] = [
                '#title' => __('Sort by label', 'directories'),
                '#type' => 'checkbox',
                '#default_value' => !empty($settings['sort']),
                '#weight' => 32,
            ];
        }
        if ($this->_application->getComponent('View')->getConfig('filters', 'facet_count')) {
            $form += [
                'hide_empty' => [
                    '#type' => 'checkbox',
                    '#title' => __('Hide empty', 'directories'),
                    '#default_value' => !empty($settings['hide_empty']),
                    '#weight' => 34,
                ],
                'hide_count' => [
                    '#type' => 'checkbox',
                    '#title' => __('Hide count', 'directories'),
                    '#default_value' => $settings['hide_count'],
                    '#weight' => 35,
                ],
                'sort_by_count' => [
                    '#type' => 'checkbox',
                    '#title' => __('Sort options by count', 'directories'),
                    '#default_value' => $settings['sort_by_count'],
                    '#weight' => 40,
                    '#states' => [
                        'invisible_or' => [
                            sprintf('[name="%s[hide_count]"]', $states_selector_prefix) => ['type' => 'checked', 'value' => true],
                            sprintf('[name="%s[sort]"]', $states_selector_prefix) => ['type' => 'checked', 'value' => true],
                        ],
                    ],
                ],
            ];
        }

        return $form;
    }

    public function fieldFilterForm(IField $field, $filterName, array $settings, $request = null, Entity\Type\Query $query = null, array $current = null, $autoSubmit = true, array $parents = [])
    {
        if ($query->view_enable_facet_count
            && (empty($settings['hide_count']) || !empty($settings['hide_empty']))
        ) {
            $field_query = $query->getFieldQuery();
            if ($settings['type'] !== 'checkboxes' // always OR if not checkboxes
                || $settings['andor'] === 'OR'
            ) {
                // Clone field query and exclude queries for the current choice field and fetch facets
                $field_query = clone $field_query;
                $field_query->removeNamedCriteria($field->getFieldName());
            }
            $facets = $this->_application->Entity_Facets($field, $field_query, $this->_getFacetOptions($field, $settings));
        }

        if (!isset($current)) {
            $option_no_escape = false;
            if (!$options = $this->_getOptions($field, $settings, $option_no_escape)) return; // no options

            if ($this->_labelSortable
                && !empty($settings['sort'])
            ) {
                asort($options);
            }

            switch ($settings['type']) {
                case 'radios':
                    $options[$this->_emptyValue] = $settings['default_text'];
                    $default_value = $this->_emptyValue;
                    break;
                case 'select':
                    $options = array_map('strip_tags', $options);
                    $options[$this->_emptyValue] = $settings['default_text'];
                    $default_value = $this->_emptyValue;
                    break;
                case 'checkboxes':
                default:
                    $default_value = null;
                    $settings['type'] = 'checkboxes';
            }

            $current = [
                '#type' => $settings['type'],
                '#select2' => empty($settings['no_fancy']),
                '#placeholder' => $settings['default_text'],
                '#options' => $options,
                '#options_valid' => array_keys($options),
                '#options_visible_count' => $settings['show_more']['num'],
                '#option_no_escape' => !empty($option_no_escape),
                '#default_value' => $default_value,
                '#entity_filter_form_type' => $settings['type'],
                '#options_disabled' => [],
                '#columns' => !$this->_application->isRunning() || empty($settings['columns']) ? 1 : $settings['columns'],
                '#options_scroll' => true,
            ];
        }

        if (isset($facets)) {
            $_request = isset($request) ? (array)$request : [];
            foreach (array_keys($current['#options']) as $value) {
                if (empty($facets[$value])) {
                    if ($value !== $this->_emptyValue) {
                        if (!empty($settings['hide_empty'])) {
                            unset($current['#options'][$value]);
                        } else {
                            if (!in_array($value, $_request)) {
                                // Disable only when the option is currently not selected
                                $current['#options_disabled'][$value] = $value;
                            }
                            $current['#options'][$value] = [
                                '#title' => $current['#options'][$value],
                                '#count' => 0,
                            ];
                        }
                    }
                } else {
                    if (empty($settings['hide_count'])) {
                        $current['#options'][$value] = [
                            '#title' => $current['#options'][$value],
                            '#count' => $facets[$value],
                        ];
                    }
                }
            }
            if (empty($settings['hide_count'])
                && !empty($settings['sort_by_count'])
            ) {
                uasort($current['#options'], function ($a, $b) {
                    return isset($a['#count']) && isset($b['#count']) && $a['#count'] > $b['#count'] ? -1 : 1;
                });
            }
        }

        return empty($current['#options']) ? null : $current;
    }

    protected function _getFacetOptions(IField $field, array $settings)
    {
        return [
            'column' => $this->_valueColumn,
        ];
    }

    abstract protected function _getOptions(IField $field, array $settings, &$noEscape = false);

    public function fieldFilterIsFilterable(IField $field, array $settings, &$value, array $requests = null)
    {
        return $settings['type'] === 'checkboxes' ? !empty($value) : $value != $this->_emptyValue;
    }

    public function fieldFilterLabels(IField $field, array $settings, $value, $form, $defaultLabel)
    {
        $ret = [];
        $prefix = $this->_prefixLabel ? $this->_application->H($defaultLabel) . ': ' : '';
        if (empty($form['#option_no_escape'])) {
            foreach ((array)$value as $_value) {
                $ret[$_value] = $prefix . $this->_application->H($form['#options'][$_value]);
            }
        } else {
            foreach ((array)$value as $_value) {
                $ret[$_value] = $prefix . $form['#options'][$_value];
            }
        }

        return $ret;
    }
}
