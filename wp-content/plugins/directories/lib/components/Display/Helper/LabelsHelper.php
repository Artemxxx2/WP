<?php
namespace SabaiApps\Directories\Component\Display\Helper;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Component\Display\Label\ILabel;
use SabaiApps\Directories\Exception;
use SabaiApps\Directories\Component\Entity\Model\Bundle;
use SabaiApps\Directories\Component\Entity\Type\IEntity;

class LabelsHelper
{
    public function help(Application $application, Bundle $bundle, $useCache = true)
    {
        if (!$useCache
            || (!$labels = $application->getPlatform()->getCache('display_labels_' . $bundle->type))
        ) {
            $labels = [];
            foreach ($application->InstalledComponentsByInterface('Display\ILabels') as $component_name) {
                if (!$application->isComponentLoaded($component_name)) continue;
                
                foreach ($application->getComponent($component_name)->displayGetLabelNames($bundle) as $label_name) {
                    if (!$application->getComponent($component_name)->displayGetLabel($label_name)) {
                        continue;
                    }
                    $labels[$label_name] = $component_name;
                }
            }
            $labels = $application->Filter('display_labels', $labels, array($bundle));
            $application->getPlatform()->setCache($labels, 'display_labels_' . $bundle->type, 0);
        }

        return $labels;
    }
    
    private $_impls = [];

    /**
     * Gets an implementation of Display\ILabel interface for a given label name
     * @param Application $application
     * @param Bundle $bundle
     * @param string $label
     * @param bool $returnFalse
     * @return ILabel
     */
    public function impl(Application $application, Bundle $bundle, $label, $returnFalse = false)
    {
        if (!isset($this->_impls[$label])) {            
            if ((!$labels = $application->Display_Labels($bundle))
                || !isset($labels[$label])
                || !$application->isComponentLoaded($labels[$label])
            ) {                
                if ($returnFalse) return false;
                throw new Exception\UnexpectedValueException(sprintf('Invalid label: %s', $label));
            }
            $this->_impls[$label] = $application->getComponent($labels[$label])->displayGetLabel($label);
        }

        return $this->_impls[$label];
    }

    public function options(Application $application, Bundle $bundle)
    {
        $options = [];
        foreach ($application->Display_Labels($bundle) as $label_name => $component_name) {
            if ((!$label = $application->Display_Labels_impl($bundle, $label_name, true))
                || (!$info = $label->displayLabelInfo($bundle))
            ) continue;

            if (!empty($info['multiple'])) {
                foreach ($info['multiple'] as $_label_name => $_label_info) {
                    $_label_name = $label_name . '-' . $_label_name;
                    $options[$_label_name] = $_label_info['label'];
                }
            } else {
                $options[$label_name] = $info['label'];
            }
        }
        return $options;
    }

    public function settingsForm(Application $application, Bundle $bundle, array $settings, array $parents, array $arrangementSelector)
    {
        $form = [];
        $labels_available = $this->help($application, $bundle);
        $arrangement_selector = sprintf('input[name="%s[]"]', $application->Form_FieldName($arrangementSelector));
        foreach (array_keys($labels_available) as $label_name) {
            if ((!$label = $application->Display_Labels_impl($bundle, $label_name, true))
                || (!$info = $label->displayLabelInfo($bundle))
            ) continue;

            if (!empty($info['multiple'])) {
                foreach ($info['multiple'] as $_label_name => $_label_info) {
                    $_label_name = $label_name . '-' . $_label_name;
                    $label_settings = isset($settings[$_label_name]['settings']) ? (array)$settings[$_label_name]['settings'] : [];
                    $form[$_label_name] = $this->_getLabelSettingsForm(
                        $application,
                        $bundle,
                        $_label_name,
                        $_label_info['label'],
                        $label,
                        $label_settings,
                        array_merge($parents, [$_label_name]),
                        $arrangement_selector
                    );
                }
            } else {
                $label_settings = isset($settings[$label_name]['settings']) ? (array)$settings[$label_name]['settings'] : [];
                $form[$label_name] = $this->_getLabelSettingsForm(
                    $application,
                    $bundle,
                    $label_name,
                    $label->displayLabelInfo($bundle, 'label'),
                    $label,
                    $label_settings,
                    array_merge($parents, [$label_name]),
                    $arrangement_selector
                );
            }
        }
        return $form;
    }

    protected function _getLabelSettingsForm(Application $application, Bundle $bundle, $labelName, $labelLabel, ILabel $label, array $settings, array $parents, $arrangementSelector)
    {
        $parents[] = 'settings';
        if ($default_settings = $label->displayLabelInfo($bundle, 'default_settings')) {
            $settings += $default_settings;
        }

        $form = [];
        if ($label->displayLabelInfo($bundle, 'labellable') !== false) {
            $form['_label'] = [
                '#type' => 'textfield',
                '#title' => __('Label text', 'directories'),
                '#default_value' => $settings['_label'],
                '#horizontal' => true,
                '#weight' => -3,
            ];
        }
        if ($label->displayLabelInfo($bundle, 'colorable') !== false) {
            $form['_color'] = [
                '#weight' => -2,
                'type' => [
                    '#type' => 'radios',
                    '#title' => __('Label color', 'directories'),
                    '#default_value' => isset($settings['_color']['type']) ? $settings['_color']['type'] : null,
                    '#options' => $application->System_Util_colorOptions() + ['custom' => __('Custom', 'directories')],
                    '#option_no_escape' => true,
                    '#default_value_auto' => true,
                    '#horizontal' => true,
                    '#columns' => 5,
                ],
                'value' => [
                    '#type' => 'colorpicker',
                    '#default_value' => isset($settings['_color']['value']) ? $settings['_color']['value'] : null,
                    '#horizontal' => true,
                    '#states' => [
                        'visible' => [
                            sprintf('input[name="%s"]', $application->Form_FieldName(array_merge($parents, ['_color', 'type']))) => ['value' => 'custom'],
                        ],
                    ],
                ],
                'text' => [
                    '#type' => 'colorpicker',
                    '#default_value' => isset($settings['_color']['text']) ? $settings['_color']['text'] : null,
                    '#horizontal' => true,
                    '#field_prefix' => __('Label text color', 'directories'),
                    '#states' => [
                        'visible' => [
                            sprintf('input[name="%s"]', $application->Form_FieldName(array_merge($parents, ['_color', 'type']))) => ['value' => 'custom'],
                        ],
                    ],
                ],
            ];
        }
        if ($label_settings_form = $label->displayLabelSettingsForm($bundle, $settings, $parents)) {
            $form += $label_settings_form;
        }
        if (!empty($form)) {
            $form = [
                '#title' => $labelLabel,
                '#states' => [
                    'enabled' => [
                        $arrangementSelector => ['value' => $labelName],
                    ],
                ],
                'settings' => $form,
            ];
        }

        return $form;
    }

    public function renderLabels(Application $application, Bundle $bundle, array $settings, array $labelSettings, IEntity $entity)
    {
        $labels = [];
        $style = empty($settings['style']) ? null : $settings['style'];
        foreach ($settings['arrangement'] as $label_name) {
            $_label_name = $label_name;
            if ($pos = strpos($label_name, '-')) {
                $label_name = substr($label_name, 0, $pos);
            }
            if (!$label = $application->Display_Labels_impl($bundle, $label_name, true)) continue;

            $label_settings = isset($labelSettings[$_label_name]['settings']) ? $labelSettings[$_label_name]['settings'] : [];
            if (!$text = $label->displayLabelText($bundle, $entity, $label_settings)) continue;

            $labellable = $label->displayLabelInfo($bundle, 'labellable');
            $color = ['type' => 'secondary'];
            if (is_array($text)) {
                if (array_key_exists(0, $text)) {
                    foreach (array_keys($text) as $text_key) {
                        $_text = $text[$text_key];
                        $_label_name .= '_' . $text_key;
                       if ($_label_text = isset($_text['label']) ? $_text['label'] : '') {
                           if ($labellable
                               || !empty($_text['translate'])
                           ) {
                               $_label_text = $application->System_TranslateString($_label_text, 'label_' . $label_name, 'display_element');
                           }
                       }
                        $labels[$_label_name] = $this->_renderLabel(
                            $application,
                            $_label_name,
                            $_label_text,
                            isset($_text['color']) ? $_text['color'] : $color,
                            $style,
                            isset($_text['attr']) ? $_text['attr'] : null
                        );
                    }
                } else {
                    if ($_label_text = isset($text['label']) ? $text['label'] : '') {
                        if ($labellable
                            || !empty($text['translate'])
                        ) {
                            $_label_text = $application->System_TranslateString($_label_text, 'label_' . $label_name, 'display_element');
                        }
                    }
                    $labels[$_label_name] = $this->_renderLabel(
                        $application,
                        $_label_name,
                        $_label_text,
                        isset($text['color']) ? $text['color'] : $color,
                        $style,
                        isset($text['attr']) ? $text['attr'] : null
                    );
                }
            } elseif (is_bool($text)) {
                $_label_text = $application->System_TranslateString($label_settings['_label'], 'label_' . $label_name, 'display_element');
                $labels[$_label_name] = $this->_renderLabel($application, $_label_name, $_label_text, $color, $style);
            }
        }

        return empty($labels) ? '' : implode(PHP_EOL, $labels);
    }

    protected function _renderLabel(Application $application, $name, $text, $color, $style, array $attr = null)
    {
        $classes = [DRTS_BS_PREFIX . 'badge'];
        $color_style = '';
        if ($color['type'] === 'custom') {
            $color_style = 'background-color:' . $application->H($color['value']) . ';';
            if (!empty($color['text'])) {
                $color_style .= 'color:' . $application->H($color['text']) . ';';
            }
        } else {
            $classes[] = DRTS_BS_PREFIX . 'badge-' . $color['type'];
        }
        if ($style === 'pill') {
            $classes[] = DRTS_BS_PREFIX . 'badge-pill';
        }
        $attr = isset($attr) ? $application->Attr($attr) : '';
        return '<span style="' . $color_style . '" class="' . implode(' ', $classes) . '" data-label-name="' . $name . '"' . $attr . '>'
            . $application->H($text)
            . '</span>';
    }

    public function labelLabels(Application $application, Bundle $bundle, array $labels)
    {
        $ret = [];
        foreach ($labels as $label_name) {
            if ($pos = strpos($label_name, '-')) {
                $label_name = substr($label_name, 0, $pos);
            }
            if (!$label = $application->Display_Labels_impl($bundle, $label_name, true)) continue;

            $info = $label->displayLabelInfo($bundle);
            if (!empty($info['multiple'])
                && $pos
                && ($key = substr($label_name, $pos + 1))
                && isset($info['multiple'][$key]['label'])
            ) {
                $ret[] = $info['multiple'][$key]['label'];
            } else {
                $ret[] = $info['label'];
            }
        }
        return $ret;
    }
}