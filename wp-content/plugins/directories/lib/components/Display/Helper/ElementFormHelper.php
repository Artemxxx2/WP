<?php
namespace SabaiApps\Directories\Component\Display\Helper;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Component\Display;
use SabaiApps\Directories\Exception;

class ElementFormHelper
{
    public function help(Application $application, Display\Model\Display $display, $element, $action, array $submittedValues = null)
    {
        if ($element instanceof Display\Model\Element) {
            $element_name = $element->name;
            $element_id = $element->id;
            $element_data = (array)$element->data;
            $is_edit = true;
            unset($element);
        } else {
            $element_name = $element;
            $element_data = [];
            $is_edit = false;
        }

        // Get bundle and element
        if (!$bundle = $application->Entity_Bundle($display->bundle_name)) {
            throw new Exception\RuntimeException('Invalid bundle: ' . $display->bundle_name);
        }
        if (!$element = $application->Display_Elements_impl($bundle, $element_name, true, false)) {
            throw new Exception\RuntimeException('Invalid display element: ' . $element_name);
        }

        if (!$element->displayElementSupports($bundle, $display)) {
            throw new Exception\RuntimeException('The element is not supported: ' . $element_name);
        }

        // Define form
        $form = array(
            '#header' => [],
            '#action' => $application->Url($action),
            '#token_reuseable' => true,
            '#enable_storage' => true,
            '#bundle' => $bundle,
            '#element_name' => $element_name,
            '#inherits' => array(
                'display_admin_add_element_' . strtolower($element_name),
            ),
            '#element_validate' => [
                [__CLASS__, '_filterSettings'],
            ],
            '#tab_style' => 'pill',
            '#tabs' => [
                'general' => array(
                    '#active' => true,
                    '#title' => _x('General', 'settings tab', 'directories'),
                    '#weight' => 1,
                ),
            ],
            'general' => array(
                '#tree' => true,
                '#tab' => 'general',
                '#weight' => 1,
            ),
            'display_id' => array(
                '#type' => 'hidden',
                '#value' => $display->id,
            ),
        );
        if (isset($element_id)) {
            $form['element_id'] = [
                '#type' => 'hidden',
                '#value' => $element_id,
            ];
        } else {
            $form['element'] = [
                '#type' => 'hidden',
                '#value' => $element_name,
            ];
            $form['parent_id'] = [
                '#type' => 'hidden',
                '#id' => 'drts-display-add-element-parent',
                '#value' => null,
            ];
        }

        $tab_weight = 5;
        $info = $element->displayElementInfo($bundle);
        $settings = isset($element_data['settings']) ? $element_data['settings'] : [];
        $settings += (array)$info['default_settings'];
        $settings_form = (array)@$element->displayElementSettingsForm(
            $bundle,
            $settings,
            $display,
            ['general', 'settings'],
            null,
            $is_edit,
            isset($submittedValues['settings']) ? $submittedValues['settings'] : []
        );
        // Labels
        $labellable = $display->type === 'entity'
            && strpos($display->name, 'summary') === 0 // only for Summary displays
            && !empty($info['labellable']);
        if ($labellable) {
            $labels_states = [
                'visible' => [
                    'input[name="general[settings][_labels][enable]"]' => ['type' => 'checked', 'value' => true],
                ],
            ];
            $settings_form['#tabs']['labels'] = [
                '#title' => _x('Labels', 'settings tab', 'directories'),
                '#states' => $labels_states,
            ];
            $settings_form['_labels'] = [
                'enable' => [
                    '#type' => 'checkbox',
                    '#title' => __('Show overlay labels', 'directories'),
                    '#horizontal' => true,
                    '#default_value' => !empty($settings['_labels']['enable']),
                ],
                'arrangement' => [
                    '#type' => 'sortablecheckboxes',
                    '#horizontal' => true,
                    '#options' => $options = $application->Display_Labels_options($bundle),
                    '#default_value' => isset($settings['_labels']['arrangement']) ? $settings['_labels']['arrangement'] : array_keys($options),
                    '#states' => $labels_states,
                ],
                'style' => [
                    '#title' => __('Label style', 'directories'),
                    '#type' => 'select',
                    '#horizontal' => true,
                    '#options' => [
                        '' => __('Default', 'directories'),
                        'pill' => __('Oval', 'directories'),
                    ],
                    '#default_value' => isset($settings['_labels']['style']) ? $settings['_labels']['style'] : '',
                    '#states' => $labels_states,
                ],
                'position' => [
                    '#title' => __('Label position', 'directories'),
                    '#type' => 'select',
                    '#horizontal' => true,
                    '#options' => [
                        'tl' => __('Top left', 'directories'),
                        'tr' => __('Top right', 'directories'),
                        'bl' => __('Bottom left', 'directories'),
                        'br' => __('Bottom right', 'directories'),
                    ],
                    '#default_value' => isset($settings['_labels']['position']) ? $settings['_labels']['position'] : 'tl',
                    '#states' => $labels_states,
                ],
            ];
        }
        // Buttons
        $buttonable = $display->type === 'entity'
            && strpos($display->name, 'summary') === 0 // only for Summary displays
            && !empty($info['buttonable']);
        if ($buttonable) {
            $buttons_states = [
                'visible' => [
                    'input[name="general[settings][_buttons][enable]"]' => ['type' => 'checked', 'value' => true],
                ],
            ];
            list($buttons_options, $buttons_defaults) = $application->Display_Buttons_options($bundle, true);
            $settings_form['#tabs']['buttons'] = [
                '#title' => _x('Buttons', 'settings tab', 'directories'),
                '#states' => $buttons_states,
            ];
            $settings_form['_buttons'] = [
                'enable' => [
                    '#type' => 'checkbox',
                    '#title' => __('Show overlay buttons', 'directories'),
                    '#horizontal' => true,
                    '#default_value' => !empty($settings['_buttons']['enable']),
                ],
                'arrangement' => [
                    '#type' => 'sortablecheckboxes',
                    '#horizontal' => true,
                    '#options' => $buttons_options,
                    '#default_value' => isset($settings['_buttons']['arrangement']) ? $settings['_buttons']['arrangement'] : $buttons_defaults,
                    '#states' => $buttons_states,
                ],
                'position' => [
                    '#title' => __('Button position', 'directories'),
                    '#type' => 'select',
                    '#horizontal' => true,
                    '#options' => [
                        'tl' => __('Top left', 'directories'),
                        'tr' => __('Top right', 'directories'),
                        'bl' => __('Bottom left', 'directories'),
                        'br' => __('Bottom right', 'directories'),
                    ],
                    '#default_value' => isset($settings['_buttons']['position']) ? $settings['_buttons']['position'] : 'tl',
                    '#states' => $buttons_states,
                ],
                'hover' => [
                    '#type' => 'checkbox',
                    '#title' => __('Show buttons on hover only', 'directories'),
                    '#horizontal' => true,
                    '#default_value' => !empty($settings['_buttons']['hover']),
                    //'#switch' => false,
                    '#states' => $buttons_states,
                ],
            ];
        }
        if (!empty($settings_form)) {
            if (isset($settings_form['#tabs'])) {
                $form['settings'] = array(
                    '#tree' => true,
                    '#tree_allow_override' => false,
                    '#weight' => 2,
                );
                foreach ($settings_form['#tabs'] as $tab_name => $tab_info) {
                    if ($tab_name === 'labels'
                        && $labellable
                    ) {
                        $_settings_form = $application->Display_Labels_settingsForm(
                            $bundle,
                            isset($settings['labels']) ? $settings['labels'] : [],
                            ['settings', $tab_name],
                            ['general', 'settings', '_labels', 'arrangement']
                        );
                    } elseif ($tab_name === 'buttons'
                        && $buttonable
                    ) {
                        $_settings_form = $application->Display_Buttons_settingsForm(
                            $bundle,
                            isset($settings['buttons']) ? $settings['buttons'] : [],
                            ['settings', $tab_name],
                            ['general', 'settings', '_buttons', 'arrangement'],
                            true
                        );
                    } else {
                        $_settings_form = (array)@$element->displayElementSettingsForm(
                            $bundle,
                            $settings,
                            $display,
                            ['settings', $tab_name],
                            $tab_name,
                            $is_edit,
                            isset($submittedValues['settings'][$tab_name]) ? $submittedValues['settings'][$tab_name] : []
                        );
                    }
                    if (!$_settings_form) continue;

                    $_tab_name = 'settings-' . $tab_name;
                    $form['settings'][$tab_name] = [
                        '#tab' => $_tab_name,
                    ] + $_settings_form;
                    if (is_string($tab_info)) {
                        $tab_info = [
                            '#title' => $tab_info,
                        ];
                    }
                    if (!isset($tab_info['#weight'])) {
                        $tab_info['#weight'] = $tab_weight += 5;
                    }
                    if (isset($form['#tabs'][$_tab_name])) {
                        $form['#tabs'][$_tab_name] += $tab_info;
                        $form['#tabs'][$_tab_name]['#disabled'] = false;
                    } else {
                        $form['#tabs'][$_tab_name] = $tab_info;
                    }
                }
                unset($settings_form['#tabs']);
            }
            if (isset($settings_form['#header'])) {
                $form['#header'] += (array)$settings_form['#header'];
                unset($settings_form['#header']);
            }
            $form['general']['settings'] = [
                '#tree' => true,
                '#tree_allow_override' => false,
                '#type' => 'fieldset',
                '#weight' => 10,
            ];
            $form['general']['settings'] += $settings_form;
        } else {
            $form['#tabs']['general']['#disabled'] = true;
        }

        // Heading settings
        if (!isset($info['headingable'])
            || false !== $info['headingable']
        ) {
            $form['heading'] = $application->Display_ElementLabelSettingsForm(
                isset($element_data['heading']) ? $element_data['heading'] : (isset($info['headingable']) && is_array($info['headingable']) ? $info['headingable'] : []),
                ['heading'],
                false
            );
            if ($display->type === 'entity') {
                $form['heading']['label_custom']['#description'] = $application->System_Util_availableTags($application->Entity_Tokens($bundle, true));
                $form['heading']['label_custom']['#description_no_escape'] = true;
            }
        }

        // Visibility settings
        if (!empty($bundle->info['parent'])) {
            $form['visibility']['hide_on_parent'] = [
                '#title' => __('Hide on parent content page', 'directories'),
                '#type' => 'checkbox',
                '#default_value' => !empty($element_data['visibility']['hide_on_parent']),
                '#weight' => 50,
            ];
        }
        if ($display->type === 'entity') {
            if (!isset($info['designable'])
                || false !== $info['designable']
            ) {
                if ($display->name === 'detailed') {
                    $form['visibility']['globalize'] = [
                        '#type' => 'checkbox',
                        '#title' => __('Add rendered content to global scope', 'directories'),
                        '#default_value' => !empty($element_data['visibility']['globalize']),
                        '#weight' => 99,
                    ];
                    $form['visibility']['globalize_remove'] = [
                        '#type' => 'checkbox',
                        '#title' => __('Remove rendered content from display', 'directories'),
                        '#default_value' => !empty($element_data['visibility']['globalize_remove']),
                        '#weight' => 99,
                        '#states' => [
                            'visible' => [
                                'input[name="visibility[globalize]"]' => ['type' => 'checked', 'value' => true],
                            ],
                        ],
                    ];
                }
            }
            if ($application->isMobileDetectable()) {
                $form['visibility']['hide_on_mobile'] = [
                    '#type' => 'checkbox',
                    '#title' => __('Hide on mobile devices', 'directories'),
                    '#default_value' => !empty($element_data['visibility']['hide_on_mobile']),
                    '#weight' => 98,
                ];
            }
        } elseif ($display->type === 'form'
            && !empty($info['can_admin_only'])
        ) {
            $form['visibility']['admin_only'] = [
                '#type' => 'checkbox',
                '#title' => __('Visible in backend only', 'directories'),
                '#default_value' => !empty($element_data['visibility']['admin_only']),
                '#weight' => 40,
            ];
        }

        // Advanced settings
        if (!isset($info['designable'])
            || false !== $info['designable']
        ) {
            $form['advanced']['css_class'] = [
                '#title' => __('CSS class', 'directories'),
                '#type' => 'textfield',
                '#default_value' => isset($element_data['advanced']['css_class']) ? $element_data['advanced']['css_class'] : null,
            ];
            $form['advanced']['css_id'] = [
                '#title' => __('CSS ID', 'directories'),
                '#type' => 'textfield',
                '#default_value' => isset($element_data['advanced']['css_id']) ? $element_data['advanced']['css_id'] : null,
                '#description' => $display->type === 'entity' ? $application->System_Util_availableTags(['%id%']) : null,
                '#description_no_escape' => true,
            ];
            if (isset($info['designable'])
                && is_array($info['designable'])
            ) {
                if (in_array('margin', $info['designable'])
                    || in_array('padding', $info['designable'])
                ) {
                    $sizes = $application->System_Util_sizeOptions(true);
                    foreach ([
                        'margin' => [
                            0 => _x('Margin', 'directories'),
                            'add' => __('Add margin', 'directories'),
                        ],
                        'padding' => [
                            0 => _x('Padding', 'directories'),
                            'add' => __('Add padding', 'directories'),
                        ],
                    ] as $css_prop => $css_prop_labels) {
                        if (in_array($css_prop, $info['designable'])) {
                            $form['advanced'][$css_prop . '_enable'] = [
                                '#title' => $css_prop_labels['add'],
                                '#type' => 'checkbox',
                                '#default_value' => !empty($element_data['advanced'][$css_prop . '_enable'])
                            ];
                            foreach ([
                                'top' => __('Top', 'directories'),
                                'right' => __('Right', 'directories'),
                                'bottom' => __('Bottom', 'directories'),
                                'left' => __('Left', 'directories'),
                            ] as $css_pos => $css_pos_label) {
                                $key = $css_prop . '_' . $css_pos;
                                $form['advanced'][$key] = [
                                    '#title' => $css_prop_labels[0] . ' - ' . $css_pos_label,
                                    '#type' => 'select',
                                    '#options' => $sizes,
                                    '#default_value' => isset($element_data['advanced'][$key]) ? $element_data['advanced'][$key] : null,
                                    '#states' => [
                                        'visible' => [
                                            'input[name="advanced[' . $css_prop . '_enable]"]' => ['type' => 'checked', 'value' => true],
                                        ],
                                    ],
                                ];
                            }
                        }
                    }
                }
                if (in_array('font', $info['designable'])) {
                    $form['advanced'] += $this->fontSettingsForm($application, isset($element_data['advanced']) ? $element_data['advanced'] : [], ['advanced']);
                }
            }

        }
        if ($display->type === 'entity'
            && !empty($info['cacheable'])
        ) {
            $form['advanced']['cache'] = $application->System_Util_cacheSettingsForm(
                isset($element_data['advanced']['cache']) ? $element_data['advanced']['cache'] : null,
                is_array($info['cacheable']) ? $info['cacheable'] : null
            );
        }

        // Let other components modify settings
        $form = $application->Filter('display_element_settings_form', $form, [$bundle, $display, $element, $element_data]);

        // Add tabs if settings available
        $weight = 30;
        $tab_weight = 50;
        foreach ([
            'heading' => _x('Heading', 'settings tab', 'directories'),
            'visibility' => _x('Visibility', 'settings tab', 'directories'),
            'advanced' => _x('Advanced', 'settings tab', 'directories'),
        ] as $key => $label) {
            $weight += 10;
            if (!empty($form[$key])) {
                $form[$key] += [
                    '#tab' => $key,
                    '#tree' => true,
                    '#tree_allow_override' => false,
                    '#horizontal_children' => true,
                    '#weight' => ++$weight,
                ];
                $form['#tabs'][$key] = [
                    '#title' => $label,
                    '#weight' => ++$tab_weight,
                ];
            }
        }

        return $form;
    }

    public static function filterSettings(array &$settings)
    {
        // Check settings and remove if empty
        $check = [
            'visibility' => ['hide_on_parent', 'hide_on_mobile'],
            'advanced' => ['css_class', 'css_id'],
        ];
        foreach (array_keys($check) as $tab) {
            foreach ($check[$tab] as $setting) {
                if (empty($settings[$tab][$setting])) {
                    unset($settings[$tab][$setting]);
                }
            }
            if (empty($settings[$tab])) $settings[$tab] = null;
        }
    }

    public static function _filterSettings(Form\Form $form, array &$settings)
    {
        self::filterSettings($settings);
    }

    protected function _getResponsiveWidthOptions()
    {
        return ['xs' => '<= 320px', 'sm' => '> 320px', 'md' => '> 480px', 'lg' => '> 720px', 'xl' => '> 960px'];
    }

    protected function _getCssSizeOptions()
    {
        return [
            1 => __('X-Small', 'directories'),
            2 => __('Small', 'directories'),
            3 => __('Medium', 'directories'),
            4 => __('Large', 'directories'),
            5 => __('X-Large', 'directories'),
        ];
    }

    public function fontSettingsForm(Application $application, array $settings, array $parents = [], $horizontal = true, $prefix = '')
    {
        return [
            'font_size' => [
                '#title' => $prefix . __('Font size', 'directories'),
                '#type' => 'select',
                '#default_value' => isset($settings['font_size']) ? $settings['font_size'] : '',
                '#options' => [
                    '' => __('Default', 'directories'),
                    'rel' => __('Relative size', 'directories'),
                    'px' => __('Absolute size', 'directories'),
                ],
                '#horizontal' => $horizontal,
            ],
            'font_size_rel' => [
                '#type' => 'slider',
                '#default_value' => isset($settings['font_size_rel']) ? $settings['font_size_rel'] : 1,
                '#min_value' => 0.1,
                '#max_value' => 3,
                '#step' => 0.1,
                '#field_prefix' => 'x',
                '#states' => [
                    'visible' => [
                        $font_size_selector = sprintf('select[name="%s"]', $application->Form_FieldName(array_merge($parents, ['font_size']))) => ['value' => 'rel'],
                    ],
                ],
                '#horizontal' => $horizontal,
            ],
            'font_size_abs' => [
                '#type' => 'slider',
                '#default_value' => isset($settings['font_size_abs']) ? $settings['font_size_abs'] : 16,
                '#min_value' => 1,
                '#max_value' => 50,
                '#step' => 1,
                '#field_suffix' => 'px',
                '#states' => [
                    'visible' => [
                        $font_size_selector => ['value' => 'px'],
                    ],
                ],
                '#horizontal' => $horizontal,
            ],
            'font_weight' => [
                '#title' => $prefix . __('Font weight', 'directories'),
                '#type' => 'select',
                '#default_value' => isset($settings['font_weight']) ? $settings['font_weight'] : '',
                '#options' => [
                    'light' => __('Light', 'directories'),
                    '' => __('Default', 'directories'),
                    'bold' => __('Bold', 'directories'),
                ],
                '#horizontal' => $horizontal,
            ],
            'font_style' => [
                '#title' => $prefix . __('Font style', 'directories'),
                '#type' => 'select',
                '#default_value' => isset($settings['font_style']) ? $settings['font_style'] : '',
                '#options' => [
                    '' => __('Default', 'directories'),
                    'italic' => __('Italic', 'directories'),
                ],
                '#horizontal' => $horizontal,
            ],
        ];
    }
}
