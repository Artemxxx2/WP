<?php
namespace SabaiApps\Directories\Component\Display\Helper;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Component\Display\Button\IButton;
use SabaiApps\Directories\Component\Entity\Model\Bundle;
use SabaiApps\Directories\Component\Entity\Type\IEntity;
use SabaiApps\Directories\Exception;

class ButtonsHelper
{
    private $_impls = [];
    
    public function help(Application $application, Bundle $bundle, $useCache = true)
    {
        if (!$useCache
            || (!$buttons = $application->getPlatform()->getCache('display_buttons_' . $bundle->type))
        ) {
            $buttons = [];
            foreach ($application->InstalledComponentsByInterface('Display\IButtons') as $component_name) {
                if (!$application->isComponentLoaded($component_name)) continue;
                
                foreach ($application->getComponent($component_name)->displayGetButtonNames($bundle) as $button_name) {
                    if (!$application->getComponent($component_name)->displayGetButton($button_name)) {
                        continue;
                    }
                    $buttons[$button_name] = $component_name;
                }
            }
            $buttons = $application->Filter('display_buttons', $buttons, [$bundle]);
            $application->getPlatform()->setCache($buttons, 'display_buttons_' . $bundle->type, 0);
        }

        return $buttons;
    }
    
    /**
     * Gets an implementation of Display\IButton interface for a given button name
     * @param Application $application
     * @param Bundle $bundle
     * @param string $button
     * @param bool $returnFalse
     * @return IButton
     */
    public function impl(Application $application, Bundle $bundle, $button, $returnFalse = false)
    {
        if (!isset($this->_impls[$button])) {            
            if ((!$buttons = $application->Display_Buttons($bundle))
                || !isset($buttons[$button])
                || (!$application->isComponentLoaded($buttons[$button]))
            ) {                
                if ($returnFalse) return false;
                throw new Exception\UnexpectedValueException(sprintf('Invalid button: %s', $button));
            }
            $this->_impls[$button] = $application->getComponent($buttons[$button])->displayGetButton($button);
        }

        return $this->_impls[$button];
    }

    public function options(Application $application, Bundle $bundle, $overlayButtonsOnly = false)
    {
        $options = $defaults = [];
        foreach (array_keys($application->Display_Buttons($bundle)) as $btn_name) {
            if (!$btn = $application->Display_Buttons_impl($bundle, $btn_name, true)) continue;

            $info = $btn->displayButtonInfo($bundle);
            if ($overlayButtonsOnly) {
                if ((isset($info['overlayable']) && $info['overlayable'] === false)
                    || (isset($info['colorable']) && $info['colorable'] === false)
                    || empty($info['default_settings']['_icon'])
                ) continue;
            }

            if (!empty($info['multiple'])) {
                foreach ($info['multiple'] as $_btn_name => $_btn_info) {
                    $_btn_name = $btn_name . '-' . $_btn_name;
                    $options[$_btn_name] = $_btn_info['label'];
                    if (!empty($_btn_info['default_checked'])) {
                        $defaults[] = $_btn_name;
                    }
                }
            } else {
                $options[$btn_name] = $info['label'];
                if (!empty($info['default_checked'])) {
                    $defaults[] = $btn_name;
                }
            }
        }
        return [$options, $defaults];
    }

    public function settingsForm(Application $application, Bundle $bundle, array $settings, array $parents, array $arrangementSelector, $overlayButtonsOnly = false)
    {
        $form = [];
        foreach (array_keys($this->help($application, $bundle)) as $btn_name) {
            if (!$btn = $this->impl($application, $bundle, $btn_name, true)) continue;

            $info = $btn->displayButtonInfo($bundle);
            if ($overlayButtonsOnly) {
                if ((isset($info['overlayable']) && $info['overlayable'] === false)
                    || (isset($info['colorable']) && $info['colorable'] === false)
                    || empty($info['default_settings']['_icon'])
                ) continue;
            }

            if (!empty($info['multiple'])) {
                foreach ($info['multiple'] as $_btn_name => $_btn_info) {
                    $_btn_name = $btn_name . '-' . $_btn_name;
                    $form[$_btn_name] = $this->_getButtonSettingsForm(
                        $application,
                        $bundle,
                        $_btn_name,
                        $_btn_info['label'],
                        $btn,
                        isset($settings[$_btn_name]['settings']) ? $settings[$_btn_name]['settings'] : [],
                        $parents,
                        $arrangementSelector,
                        $overlayButtonsOnly
                    );
                }
            } else {
                $form[$btn_name] = $this->_getButtonSettingsForm(
                    $application,
                    $bundle,
                    $btn_name,
                    $btn->displayButtonInfo($bundle, 'label'),
                    $btn,
                    isset($settings[$btn_name]['settings']) ? $settings[$btn_name]['settings'] : [],
                    $parents,
                    $arrangementSelector,
                    $overlayButtonsOnly
                );
            }
        }
        return $form;
    }

    protected function _getButtonSettingsForm(Application $application, Bundle $bundle, $btnName, $btnLabel, IButton $btn, array $settings, array $parents, $arrangementSelector, $overlayButton)
    {
        $_parents = $parents;
        $_parents[] = $btnName;
        $btn_parents = $_parents;
        $btn_parents[] = 'settings';
        if ($default_settings = $btn->displayButtonInfo($bundle, 'default_settings')) {
            $settings += $default_settings;
        }
        $ret = [
            '#title' => $btnLabel,
            '#weight' => (int)$btn->displayButtonInfo($bundle, 'weight'),
            '#states' => [
                'enabled' => [
                    sprintf('input[name="%s[]"]', $application->Form_FieldName($arrangementSelector)) => ['value' => $btnName],
                ],
            ],
            'settings' => [
                '#element_validate' => [[[$this, 'validateButtonSettings'], [$_parents]]],
            ],
        ];
        if ($overlayButton) {
            $ret['settings']['_hide_label'] = [
                '#type' => 'hidden',
                '#default_value' => true,
            ];
        } else {
            $ret['settings']['_hide_label'] = [
                '#type' => 'checkbox',
                '#title' => __('Hide label', 'directories'),
                '#default_value' => !empty($settings['_hide_label']),
                '#horizontal' => true,
                '#weight' => -3,
            ];
        }
        if ($btn->displayButtonInfo($bundle, 'colorable') !== false) {
            if ($overlayButton) {
                $ret['settings']['_color'] = [
                    '#type' => 'hidden',
                    '#default_value' => 'link',
                ];
            } else {
                $ret['settings']['_color'] = [
                    '#type' => 'radios',
                    '#title' => __('Button color', 'directories'),
                    '#default_value' => isset($settings['_color']) ? $settings['_color'] : null,
                    '#options' => $application->System_Util_colorOptions(true, true),
                    '#option_no_escape' => true,
                    '#horizontal' => true,
                    '#weight' => -2,
                    '#columns' => 6,
                ];
            }
            $ret['settings']['_link_color'] = [
                '#type' => 'colorpicker',
                '#title' => __('Link color', 'directories'),
                '#default_value' => isset($settings['_link_color']) ? $settings['_link_color'] : ($overlayButton ? '#EEEEEE' : null),
                '#horizontal' => true,
                '#weight' => -1,
                '#states' => [
                    'visible' => [
                        sprintf('input[name="%s"]', $application->Form_FieldName(array_merge($btn_parents, ['_color']))) => ['value' => 'link'],
                    ],
                ],
            ];
        }
        if ($btn->displayButtonInfo($bundle, 'iconable') !== false) {
            $ret['settings']['_icon'] = [
                '#type' => 'iconpicker',
                '#title' => __('Button icon', 'directories'),
                '#default_value' => $settings['_icon'],
                '#horizontal' => true,
                '#weight' => -2,
            ];
        }
        if ($btn->displayButtonInfo($bundle, 'labellable') !== false) {
            $ret['settings']['_label_type'] = [
                '#type' => 'select',
                '#title' => __('Button label', 'directories'),
                '#options' => [
                    'default' => __('Custom label', 'directories'),
                    'field' => __('Select field', 'directories'),
                ],
                '#default_value' => isset($settings['_label_type']) ? $settings['_label_type'] : 'default',
                '#horizontal' => true,
                '#weight' => -5,
                '#states' => [
                    'visible' => [
                        sprintf('[name="%s"]', $application->Form_FieldName(array_merge($btn_parents, ['_hide_label']))) => ['type' => 'checked', 'value' => false],
                    ],
                ],
            ];
            $ret['settings']['_label'] = [
                '#type' => 'textfield',
                '#placeholder' => __('Custom label', 'directories'),
                '#default_value' => isset($settings['_label']) ? $settings['_label'] : null,
                '#horizontal' => true,
                '#weight' => -4,
                '#states' => [
                    'visible' => [
                        sprintf('[name="%s"]', $application->Form_FieldName(array_merge($btn_parents, ['_hide_label']))) => ['type' => 'checked', 'value' => false],
                        sprintf('select[name="%s"]', $application->Form_FieldName(array_merge($btn_parents, ['_label_type']))) => ['value' => 'default'],
                    ],
                ],
            ];
            $fields = $application->Entity_Field_options($bundle->name, ['interface' => 'Field\Type\ILabellable', 'return_disabled' => true]);
            if (!empty($fields[0])
                || !empty($fields[1])
            ) {
                $ret['settings']['_label_field'] = [
                    '#type' => 'select',
                    '#options' => $fields[0],
                    '#options_disabled' => array_keys($fields[1]),
                    '#default_value' => isset($settings['_label_field']) ? $settings['_label_field'] : null,
                    '#horizontal' => true,
                    '#weight' => -4,
                    '#states' => [
                        'visible' => [
                            sprintf('select[name="%s"]', $application->Form_FieldName(array_merge($btn_parents, ['_label_type']))) => ['value' => 'field'],
                        ],
                    ],
                ];
            } else {
                $ret['settings']['_label_type']['#options_disabled'][] = 'field';
            }
        }
        if ($btn_settings_form = $btn->displayButtonSettingsForm($bundle, $settings, $btn_parents)) {
            $ret['settings'] += $btn_settings_form;
        }

        return $ret;
    }

    public function validateButtonSettings($form, &$value, $element, $parents)
    {
        $settings = $form->getValue($parents);
        if (!empty($settings['_hide_label'])
            && !strlen($value['_icon'])
        ) {
            $error = __('Icon may not be empty if label is hidden', 'directories');
            $form->setError($error, $element['#name'] . '[_icon]');
        }
    }

    public function renderButtons(Application $application, Bundle $bundle, array $settings, array $btnSettings, $displayName, IEntity $entity)
    {
        $buttons = [];
        if (!empty($settings['arrangement'])) {
            $apply_color = empty($settings['dropdown']);
            foreach ($settings['arrangement'] as $btn_name) {
                if ($link = $this->_getButtonLink($application, $bundle, $btn_name, $entity, $btnSettings[$btn_name], $displayName, $apply_color)) {
                    $buttons[$btn_name] = $link;
                }
            }
        }
        if (empty($buttons)) return '';

        $options = [
            'size' => isset($settings['size']) ? $settings['size'] : null,
            'tooltip' => empty($settings['dropdown']) && !empty($settings['tooltip']),
            'label' => true,
            'group' => true,
        ];

        if (empty($settings['dropdown'])) {
            if (count($buttons) === 1) {
                if (!$apply_color) { // regenerate link with color if color has not been applied
                    $btn_name = current(array_keys($buttons));
                    $buttons = [$this->_getButtonLink($application, $bundle, $btn_name, $entity, $btnSettings[$btn_name], $displayName)];
                }
                return $application->ButtonLinks($buttons, $options);
            }
        } else {
            array_unshift($buttons, $application->LinkTo($settings['dropdown_label'], '#', ['active' => true, 'icon' => $settings['dropdown_icon']]));
            return $application->DropdownButtonLinks(
                $buttons,
                ['size' => $settings['size'], 'right' => !empty($settings['dropdown_right']), 'tooltip' => true, 'label' => true, 'color' => 'outline-secondary']
            );
        }

        if (empty($settings['separate'])) return $application->ButtonLinks($buttons, $options);

        return $application->ButtonToolbar($buttons, $options);
    }

    protected function _getButtonLink(Application $application, Bundle $bundle, $btnName, $entity, array $settings, $displayName, $applyColor = true)
    {
        $_btn_name = null;
        if (strpos($btnName, '-')) {
            list($btn_name, $_btn_name) = explode('-', $btnName);
        } else {
            $btn_name = $btnName;
        }
        if (!$btn = $application->Display_Buttons_impl($bundle, $btn_name, true)) return;

        $btn_settings = isset($settings['settings']) ? $settings['settings'] : [];
        if ($applyColor) {
            if (!empty($btn_settings['_color'])) {
                $btn_settings['_class'] = DRTS_BS_PREFIX . 'btn-' . $btn_settings['_color'];
                $btn_settings['_style'] = $btn_settings['_color'] === 'link' ? 'color:' . $btn_settings['_link_color'] . ';' : '';
            } else {
                $btn_settings['_class'] = DRTS_BS_PREFIX . 'btn-outline-secondary';
                $btn_settings['_style'] = '';
            }
        } else {
            $btn_settings['_class'] = $btn_settings['_style'] = '';
        }
        if ($btn->displayButtonInfo($bundle, 'labellable') !== false) {
            if (isset($btn_settings['_label_type'])
                && $btn_settings['_label_type'] === 'field'
            ) {
                if (($field = $application->Entity_Field($entity, $btn_settings['_label_field']))
                    && ($field_type = $application->Field_Type($field->getFieldType(), true))
                    && $field_type instanceof ILabellable
                    && ($labels = $field_type->fieldLabellableLabels($field, $entity))
                ) {
                    $btn_settings['_label'] = $labels[0];
                } else {
                    $btn_settings['_label'] = '';
                }
            } else {
                if (strlen($btn_settings['_label'])) {
                    $btn_settings['_label'] = $application->System_TranslateString($btn_settings['_label'], 'button_custom_label', 'display_element');
                }
            }
        }
        if (!$link = $btn->displayButtonLink($bundle, $entity, $btn_settings, $displayName)) return;

        if (!empty($btn_settings['_hide_label'])) {
            if (is_array($link)) {
                // Dropdown button
                $link[0]->setAttribute('title', strip_tags($link[0]->getLabel()))
                    ->setAttribute('data-button-name', $btnName)
                    ->setLabel('');
            } else {
                $link->setAttribute('title', strip_tags($link->getLabel()))
                    ->setAttribute('data-button-name', $btnName)
                    ->setLabel('');
                if (!$link->getAttribute('rel')) {
                    $link->setAttribute('rel', 'nofollow');
                }
            }
        } else {
            if (!is_array($link)) {
                $link->setAttribute('data-button-name', $btnName);
                if (!$link->getAttribute('rel')) {
                    $link->setAttribute('rel', 'nofollow');
                }
            }
        }

        return $link;
    }

    public function buttonLabels(Application $application, Bundle $bundle, array $buttons)
    {
        $labels = [];
        foreach ($buttons as $btn_name) {
            if ($multiple = strpos($btn_name, '-')) {
                list($btn_name, $_btn_name) = explode('-', $btn_name);
            }
            if (!$btn = $application->Display_Buttons_impl($bundle, $btn_name, true)) continue;

            $info = $btn->displayButtonInfo($bundle);
            if ($multiple) {
                if (!isset($info['multiple'][$_btn_name]['label'])) continue;

                $labels[] = $info['multiple'][$_btn_name]['label'];
            } else {
                $labels[] = $info['label'];
            }
        }
        return $labels;
    }
}