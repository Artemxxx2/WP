<?php
namespace SabaiApps\Directories\Component\View\Helper;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Form;
use SabaiApps\Directories\Component\Display\Controller\Admin\AddDisplay;
use SabaiApps\Directories\Component\View\Model\View;

class SettingsFormHelper
{
    public function help(Application $application, Entity\Model\Bundle $bundle, $view, array $submitValues = null)
    {
        if ($view instanceof View) {
            $label = $view->getLabel();
            $name = $view->name;
            $mode = $view->mode;
            $settings = $view->data['settings'];
            $is_default_view = (bool)$view->default;
        } else {
            $label = $name = null;
            $mode = isset($view['mode']) ? $view['mode'] : null;
            $settings = isset($view['settings']) ? $view['settings'] : [];
            $is_default_view = !empty($view['is_default']);
        }

        $form = [
            '#tabs' => [
                'general' => [
                    '#title' => _x('General', 'settings tab', 'directories'),
                    '#weight' => 1,
                ],
            ],
            '#tab_style' => 'pill',
            'general' => [
                '#tree' => true,
                '#tab' => 'general',
                'label' => [
                    '#type' => 'textfield',
                    '#title' => __('View label', 'directories'),
                    '#description' => __('Enter a label used for administration purpose only.', 'directories'),
                    '#max_length' => 255,
                    '#required' => true,
                    '#horizontal' => true,
                    '#default_value' => $label,
                ],
                'name' => [
                    '#type' => 'textfield',
                    '#title' => __('View name', 'directories'),
                    '#description' => __('Enter a unique name so that that it can be easily referenced. Only lowercase alphanumeric characters and underscores are allowed.', 'directories'),
                    '#max_length' => 50,
                    '#required' => true,
                    '#regex' => '/^[a-z0-9_]+$/',
                    '#horizontal' => true,
                    '#slugify' => isset($name),
                    '#states' => isset($name) ? null : [
                        'slugify' => [
                            'input[name="general[label]"]' => ['type' => 'filled', 'value' => true],
                        ],
                    ],
                    '#element_validate' => [
                        function (Form\Form $form, &$value, $element) use ($application, $bundle, $view) {
                            $query = $application->getModel('View', 'View')->bundleName_is($bundle->name)->name_is($value);
                            if ($view instanceof View) {
                                $query->id_isNot($view->id);
                            }
                            if ($query->count()) {
                                $form->setError(__('The name is already taken.', 'directories'), $element);
                            }
                        },
                    ],
                    '#default_value' => $name,
                ],
                'mode' => [
                    '#title' => __('View mode', 'directories'),
                    '#type' => 'select',
                    '#horizontal' => true,
                    '#options' => [],
                    '#default_value' => $mode,
                ],
                'mode_settings' => [
                    '#tree' => true,
                ],
            ],
        ];

        $features_disabled_by_mode = [];
        foreach (array_keys($application->View_Modes()) as $view_mode_name) {
            if ((!$view_mode = $application->View_Modes_impl($view_mode_name, true))
                || !$view_mode->viewModeSupports($bundle)
            ) continue;

            $form['general']['mode']['#options'][$view_mode_name] = $view_mode->viewModeInfo('label');
            $form['general']['mode_settings'][$view_mode_name] = $application->View_Modes_settingsForm(
                $view_mode,
                $bundle,
                $mode === $view_mode_name ? $settings : [],
                ['general', 'mode_settings', $view_mode_name],
                $submitValues
            );
            $form['general']['mode_settings'][$view_mode_name]['#states'] = [
                'visible' => [
                    'select[name="general[mode]"]' => ['value' => $view_mode_name],
                ],
            ];
            $features_disabled_by_mode[$view_mode_name] = (array)$view_mode->viewModeInfo('features_disabled');
        }
        
        $states = [];
        foreach ($features_disabled_by_mode as $view_mode => $view_features) {
            foreach ($view_features as $feature_name) {
                $states[$feature_name][] = $view_mode;
            }
        }

        $form['#validate'][] = function ($form) use ($features_disabled_by_mode) {
            $mode_selected = $form->values['general']['mode'];
            if (!empty($features_disabled_by_mode[$mode_selected])) {
                foreach ($features_disabled_by_mode[$mode_selected] as $feature_disabled) {
                    unset($form->values[$feature_disabled]);
                }
            }
        };
        $features = [
            'sort' => [
                'label' => __('Sort Settings', 'directories'),
                'weight' => 5,
            ],
            'pagination' => [
                'label' => _x('Pagination', 'tab', 'directories'),
                'weight' => 10,
            ],
            'filter' => [
                'label' => __('Filter Settings', 'directories'),
                'weight' => 20,
            ],
            'other' => [
                'label' => __('Other', 'directories'),
                'weight' => 25,
            ],
        ];
        if (empty($bundle->info['internal'])) {
            $features['query'] = [
                'label' => __('Query Settings', 'directories'),
                'weight' => 15,
            ];
        }
        foreach ($features as $feature_name => $feature) {
            if (!$feature_settings_form = $this->feature(
                $application,
                $bundle,
                $feature_name,
                isset($settings[$feature_name]) ? $settings[$feature_name] : [],
                $is_default_view,
                [$feature_name],
                isset($submitValues[$feature_name]) ? $submitValues[$feature_name] : null
            )) continue;

            $form[$feature_name] = [
                '#tree' => true,
                '#tabs' => [
                    $feature_name => [
                        '#title' => $feature['label'],
                        '#weight' => $feature['weight'],
                    ],
                ],
                '#tab' => $feature_name,
            ] + $feature_settings_form;
            if (!empty($states[$feature_name])) {
                $form[$feature_name]['#states']['invisible']['[name="general[mode]"]']
                    = $form[$feature_name]['#tabs'][$feature_name]['#states']['invisible']['[name="general[mode]"]']
                    = ['type' => 'one', 'value' => $states[$feature_name]];
            }
        }

        return $application->Filter('view_feature_settings_form', $form, [$bundle, $settings, $submitValues]);
    }

    public function feature(Application $application, Entity\Model\Bundle $bundle, $feature, array $settings, $isDefaultView = false, array $parents = [], array $submitValues = null)
    {
        switch ($feature) {
            case 'sort':
                $sort_options_html = $application->Entity_Sorts_options($bundle, true);
                $sort_options = array_map('strip_tags', $sort_options_html);
                $form = [
                    '#element_validate' => [
                        function (Form\Form $form, &$value, $element) {
                            if (!empty($value['default'])) {
                                if (empty($value['options'])) {
                                    $value['options'] = [];
                                }
                                if (!in_array($value['default'], $value['options'])) {
                                    $value['options'][] = $value['default'];
                                }
                            }
                        },
                    ],
                    'default' => array(
                        '#type' => 'select',
                        '#default_value' => isset($settings['default']) ? $settings['default'] : null,
                        '#title' => __('Default sort order', 'directories'),
                        '#options' => $sort_options,
                        '#required' => true,
                        '#display_unrequired' => true,
                        '#horizontal' => true,
                        '#weight' => 5,
                    ),
                    'secondary' => array(
                        '#type' => 'select',
                        '#default_value' => isset($settings['secondary']) ? $settings['secondary'] : null,
                        '#title' => __('Secondary sort order', 'directories'),
                        '#options' => ['' => __('— Select —', 'directories')] +  $sort_options,
                        '#required' => false,
                        '#horizontal' => true,
                        '#weight' => 6,
                    ),
                    'options' => array(
                        '#type' => 'sortablecheckboxes',
                        '#options' => $sort_options_html,
                        '#option_no_escape' => true,
                        '#default_value' => isset($settings['options']) ? $settings['options'] : array(current(array_keys($sort_options_html))),
                        '#title' => __('Sort options', 'directories'),
                        '#horizontal' => true,
                        '#weight' => 1,
                    ),
                ];
                if ($application->Entity_BundleTypeInfo($bundle, 'featurable')) {
                    $form['stick_featured'] = [
                        '#type' => 'checkbox',
                        '#title' => __('Show featured items first', 'directories'),
                        '#default_value' => !empty($settings['stick_featured']),
                        '#horizontal' => true,
                        '#weight' => 10,
                    ];
                    if ($isDefaultView) {
                        $form['stick_featured_term_only'] = [
                            '#type' => 'checkbox',
                            //'#switch' => false,
                            '#title' => __('Show featured items first on single term pages only', 'directories'),
                            '#default_value' => !empty($settings['stick_featured_term_only']),
                            '#horizontal' => true,
                            '#weight' => 11,
                            '#states' => array(
                                'visible' => array(
                                    sprintf('input[name="%s"]', $application->Form_FieldName(array_merge($parents, ['stick_featured']))) => ['type' => 'checked', 'value' => true]
                                ),
                            ),
                        ];
                    }
                }
                return $form;

            case 'pagination':
                $no_pagination_selector = sprintf('input[name="%s"]', $application->Form_FieldName(array_merge($parents, ['no_pagination'])));
                $type_selector = sprintf('select[name="%s"]', $application->Form_FieldName(array_merge($parents, ['type'])));
                return [
                    '#element_validate' => [
                        function (Form\Form $form, &$value, $element)
                        {
                            if (!in_array($form->values['general']['mode'], ['list'])) {
                                $value['type'] = '';
                            }
                        }
                    ],
                    'no_pagination' => [
                        '#type' => 'checkbox',
                        '#title' => __('Disable pagination', 'directories'),
                        '#default_value' => !empty($settings['no_pagination']) || (!isset($settings['no_pagination']) && !empty($bundle->info['internal'])),
                        '#horizontal' => true,
                        '#weight' => 1,
                    ],
                    'type' => [
                        '#type' => 'select',
                        '#title' => __('Pagination type', 'directories'),
                        '#options' => [
                            '' => __('Default', 'directories'),
                            'load_more' => __('Load more button', 'directories'),
                        ],
                        '#default_value' => isset($settings['type']) ? $settings['type'] : '',
                        '#states' => array(
                            'visible' => array(
                                $no_pagination_selector => ['type' => 'checked', 'value' => false],
                                '[name="general[mode]"]' => ['type' => 'one', 'value' => ['list']],
                            ),
                        ),
                        '#horizontal' => true,
                        '#weight' => 3,
                    ],
                    'perpage' => [
                        '#type' => 'slider',
                        '#title' => __('Items per page', 'directories'),
                        '#default_value' => isset($settings['perpage']) ? $settings['perpage'] : 20,
                        '#integer' => true,
                        '#required' => true,
                        '#display_unrequired' => true,
                        '#max_value' => $application->Filter('view_feature_settings_pagination_perpage_max', 200, [$bundle, $isDefaultView]),
                        '#min_value' => 1,
                        '#horizontal' => true,
                        '#states' => array(
                            'visible' => array(
                                $no_pagination_selector => ['type' => 'checked', 'value' => false],
                            ),
                        ),
                        '#weight' => 5,
                    ],
                    'allow_perpage' => [
                        '#type' => 'checkbox',
                        '#title' => __('Allow selection of number of items per page', 'directories'),
                        '#default_value' => !empty($settings['allow_perpage']),
                        '#horizontal' => true,
                        '#states' => array(
                            'visible' => array(
                                $no_pagination_selector => ['type' => 'checked', 'value' => false],
                                $type_selector => ['value' => ''],
                            ),
                        ),
                        '#weight' => 10,
                    ],
                    'perpages' => [
                        '#type' => 'checkboxes',
                        '#integer' => true,
                        '#title' => __('Allowed number of items per page', 'directories'),
                        '#default_value' => isset($settings['perpages']) ? $settings['perpages'] : array(10, 20, 50),
                        '#options' => array_combine($perpages = $application->Filter('view_pagination_perpages', array(10, 12, 15, 20, 24, 30, 36, 48, 50, 60, 100, 120, 200)), $perpages),
                        '#horizontal' => true,
                        '#states' => array(
                            'visible' => array(
                                $no_pagination_selector => ['type' => 'checked', 'value' => false],
                                $type_selector => ['value' => ''],
                                sprintf('input[name="%s"]', $application->Form_FieldName(array_merge($parents, ['allow_perpage']))) => ['type' => 'checked', 'value' => true],
                            ),
                        ),
                        '#weight' => 15,
                        '#columns' => 6,
                    ],
                    'load_more_label' => [
                        '#type' => 'textfield',
                        '#title' => __('Custom "Load More" button label', 'directories'),
                        '#states' => array(
                            'visible' => array(
                                $no_pagination_selector => ['type' => 'checked', 'value' => false],
                                '[name="general[mode]"]' => ['type' => 'one', 'value' => ['list', 'table']],
                                $type_selector => ['value' => 'load_more'],
                            ),
                        ),
                        '#horizontal' => true,
                        '#weight' => 5,
                        '#placeholder' => __('Load More', 'directories'),
                        '#default_value' => isset($settings['load_more_label']) ? $settings['load_more_label'] : '',
                    ],
                ];

            case 'filter':
                if (!$application->getComponent('View')->isFilterable($bundle)) return;

                $show_filters_selector = sprintf('input[name="%s"]', $application->Form_FieldName(array_merge($parents, ['show'])));
                $show_in_modal_selector = sprintf('input[name="%s"]', $application->Form_FieldName(array_merge($parents, ['show_modal'])));
                $ret = [
                    '#element_validate' => [
                        function (Form\Form $form, &$value, $element) {
                            if (!empty($value['show_modal'])) {
                                $value['shown'] = false;
                            }
                        }
                    ],
                    'show' => [
                        '#type' => 'checkbox',
                        '#title' => __('Show filter form', 'directories'),
                        '#default_value' => !empty($settings['show']),
                        '#horizontal' => true,
                        '#weight' => 5,
                    ],
                    'shown' => [
                        '#type' => 'checkbox',
                        '#title' => __('Disable collapsing filter form', 'directories'),
                        '#default_value' => !empty($settings['shown']),
                        '#horizontal' => true,
                        '#weight' => 11,
                        '#states' => [
                            'visible' => [
                                $show_filters_selector => ['type' => 'checked', 'value' => true],
                                $show_in_modal_selector => ['type' => 'checked', 'value' => false]
                            ],
                        ],
                    ],
                ];

                if ($isDefaultView) {
                    $ret['show_mobile_only'] = [
                        '#type' => 'checkbox',
                        '#title' => __('Show filter form on mobile only', 'directories'),
                        '#default_value' => !empty($settings['show_mobile_only']),
                        '#horizontal' => true,
                        '#weight' => 7,
                        '#states' => [
                            'visible' => [
                                $show_filters_selector => ['type' => 'checked', 'value' => true],
                            ],
                        ],
                    ];
                }

                $displays = AddDisplay::existingDisplays($application, $bundle->name,'default', 'filters');
                if (count($displays) > 1) {
                    $ret['display'] = [
                        '#type' => 'select',
                        '#title' => __('Select filter group', 'directories'),
                        '#options' => $displays,
                        '#horizontal' => true,
                        '#default_value' => isset($settings['display']) && isset($displays[$settings['display']]) ? $settings['display'] : null,
                        '#weight' => 7,
                    ];
                } else {
                    $ret['display'] = [
                        '#type' => 'hidden',
                        '#default_value' => 'default',
                    ];
                }

                if (empty($bundle->info['parent'])) {
                    $ret['show_modal'] = [
                        '#type' => 'checkbox',
                        '#title' => __('Show filter form in modal window', 'directories'),
                        '#default_value' => !empty($settings['show_modal']),
                        '#horizontal' => true,
                        '#weight' => 10,
                        '#states' => [
                            'visible' => [
                                $show_filters_selector => ['type' => 'checked', 'value' => true],
                            ],
                        ],
                    ];
                }

                return $ret;

            case 'query':
                $fields = $application->Entity_Field($bundle);
                $form = [
                    '#element_validate' => [
                        function (Form\Form $form, &$value, $element) {
                            if (empty($value['fields'])) return;

                            $queries = [];
                            foreach (array_filter($value['fields']) as $query) {
                                if (!strlen($query['field'])
                                    || !strlen(trim($query['query']))
                                ) continue;

                                $queries[$query['field']] = $query['query'];
                            }
                            $value['fields'] = $queries;
                        }
                    ],
                    'fields' => [
                        '#title' => __('Query by field', 'directories'),
                        '#horizontal' => true,
                        '#weight' => 5,
                    ],
                    'limit' => [
                        '#type' => 'number',
                        '#title' => __('Max number of items to query', 'directories'),
                        '#description' => __('Enter 0 for no limit.', 'directories'),
                        '#default_value' => empty($settings['limit']) ? 0 : (int)$settings['limit'],
                        '#horizontal' => true,
                        '#integer' => true,
                        '#weight' => 10,
                    ],
                ];
                if (isset($submitValues['fields'])) {
                    // coming from form submission
                    // need to check request values since fields may have been added/removed
                    $queries = empty($submitValues['fields']) ? array(null) : $submitValues['fields'];
                } else {
                    $queries = [];
                    if (!empty($settings['fields'])) {
                        foreach ($settings['fields'] as $field_name => $query_str) {
                            $queries[] = ['field' => $field_name, 'query' => $query_str];
                        }
                    }
                    $queries[] = null;
                }
                foreach ($queries as $i => $query) {
                    $form['fields'][$i] = [
                        '#type' => 'field_query',
                        '#fields' => $fields,
                        '#default_value' => $query,
                    ];
                }
                $form['fields']['_add'] = [
                    '#type' => 'addmore',
                    '#next_index' => ++$i,
                ];

                return $form;

            case 'other':
                $form = [
                    'num' => [
                        '#type' => 'checkbox',
                        '#title' => __('Show number of items found', 'directories'),
                        '#default_value' => !empty($settings['num']),
                        '#horizontal' => true,
                        '#weight' => 5,
                    ],
                    'not_found' => [
                        '#weight' => 20,
                        'custom' => [
                            '#type' => 'checkbox',
                            '#title' => __('Customize "Not found" text', 'directories'),
                            '#default_value' => !empty($settings['not_found']['custom']),
                            '#horizontal' => true,
                        ],
                        'html' => [
                            '#type' => 'textarea',
                            '#rows' => 3,
                            '#default_value' => isset($settings['not_found']['html']) ? $settings['not_found']['html'] : null,
                            '#horizontal' => true,
                            '#states' => [
                                'visible' => [
                                    sprintf('input[name="%s"]', $application->Form_FieldName(array_merge($parents, ['not_found', 'custom']))) => ['type' => 'checked', 'value' => true],
                                ],
                            ],
                            '#description' => $application->System_Util_availableTags([
                                '%current_user_id%',
                                '%current_user_name%',
                                '%current_user_display_name%',
                            ]),
                            '#description_no_escape' => true,
                        ],
                    ],
                ];

                if (empty($bundle->info['is_taxonomy'])
                    && empty($bundle->info['internal'])
                    && !empty($bundle->info['public'])
                    && empty($bundle->info['parent'])
                    && empty($bundle->info['is_user'])
                    && $application->isComponentLoaded('FrontendSubmit')
                ) {
                    $form['add'] = [
                        'show' => [
                            '#type' => 'checkbox',
                            '#title' => sprintf(__('Show "%s" button', 'directories'), $bundle->getLabel('add')),
                            '#default_value' => !empty($settings['add']['show']),
                            '#horizontal' => true,
                        ],
                        'show_label' => [
                            '#type' => 'checkbox',
                            '#title' => sprintf(__('Show "%s" button with label', 'directories'), $bundle->getLabel('add')),
                            '#default_value' => !empty($settings['add']['show_label']),
                            '#horizontal' => true,
                            '#states' => [
                                'visible' => [
                                    sprintf('input[name="%s"]', $application->Form_FieldName(array_merge($parents, ['add', 'show']))) => ['type' => 'checked', 'value' => true],
                                ],
                            ],
                        ],
                    ];
                    $form['add']['#weight'] = 10;
                }

                return $form;
        }
    }
}
add_filter('drts_view_pagination_perpages', function ($perpages) { $perpages[] = 300; return $perpages; });