<?php
namespace SabaiApps\Directories\Component\Display\Element;

use SabaiApps\Directories\Component\Entity\Model\Bundle;
use SabaiApps\Directories\Component\Display\Model\Display;
use SabaiApps\Directories\Component\Display\Model\Element;
use SabaiApps\Directories\Component\Form\Form;

class ColumnElement extends AbstractElement
{
    protected function _displayElementInfo(Bundle $bundle)
    {
        return [
            'type' => 'utility',
            'label' => _x('Column', 'display element name', 'directories'),
            'default_settings' => [
                'width' => 4,
                'responsive' => [
                    'xs' => ['width' => 12],
                    'sm' => ['width' => 'inherit'],
                    'md' => ['width' => 'inherit'],
                    'lg' => ['width' => 'inherit'],
                    'xl' => ['width' => 'inherit'],
                    'grow' => true,
                ],
                'hide_empty' => true,
            ],
            'containable' => true,
            'parent_element_name' => 'columns',
            'icon' => 'fas fa-columns',
            'designable' => ['padding'],
            'labellable' => true,
            'buttonable' => true,
        ];
    }
    
    public function displayElementSettingsForm(Bundle $bundle, array $settings, Display $display, array $parents = [], $tab = null, $isEdit = false, array $submitValues = [])
    {
        $widths = $this->_getWidthOptions();
        $form = [
            'width' => [
                '#title' => __('Column width', 'directories'),
                '#type' => 'select',
                '#options' => $widths + [
                    'responsive' => __('Responsive', 'directories'),
                ],
                '#default_value' => $settings['width'],
                '#horizontal' => true,
            ],
            'responsive' => [
                '#states' => [
                    'visible' => [
                        sprintf('select[name="%s"]', $this->_application->Form_FieldName(array_merge($parents, ['width']))) => ['value' => 'responsive'],
                    ],
                ],
                '#element_validate' => [function(Form $form, &$value, $element) {
                    foreach (['xl', 'lg', 'md', 'sm', 'xs'] as $key) {
                        if (!empty($value[$key]['width'])
                            && $value[$key]['width'] !== 'inherit'
                        ) {
                            $width = $value[$key]['width'];

                            break;
                        }
                    }
                    if (empty($width)) {
                        $form->setError(__('Please select at least one non-empty width.', 'directories'), $element);
                    }
                }],
            ],
            'hide_empty' => [
                '#type' => 'checkbox',
                '#title' => __('Hide if empty', 'directories'),
                '#default_value' => !empty($settings['hide_empty']),
                '#horizontal' => true,
            ],
        ];
        foreach ($this->_getResponsiveWidthOptions() as $key => $title) {
            $options = [0 => __('Hidden', 'directories')] + $widths;
            if ($can_inherit = isset($can_inherit)) {
                $options = ['inherit' => __('Inherit from smaller', 'directories')] + $options;
                $default = 'inherit';
            } else {
                $default = 12;
            }
            if (isset($settings['responsive'][$key]['width'])) {
                $current_value = $settings['responsive'][$key]['width'];
                if ($current_value !== 'inherit') $current_value = (int)$current_value;
            } else {
                $current_value = $default;
            }
            $form['responsive'][$key] = [
                'width' => [
                    '#field_prefix' => $title,
                    '#type' => 'select',
                    '#options' => $options,
                    '#default_value' => $current_value,
                    '#horizontal' => true,
                    '#description' => sprintf(
                        __('Select the display width of this column when the container of the column is %s.', 'directories'),
                        $title
                    ),
                    '#empty_value' => $can_inherit ? 'inherit' : null,
                    '#attributes' => ['class' => DRTS_BS_PREFIX . 'custom-select'],
                ],
            ];
        }
        $form['responsive']['grow'] = [
            '#type' => 'checkbox',
            '#horizontal' => true,
            '#title' => __('Stretch to fill space', 'directories'),
            '#default_value' => !empty($settings['responsive']['grow']),
        ];
        
        return $form;
    }
    
    protected function _getResponsiveWidthOptions()
    {
        return ['xs' => '<= 320px', 'sm' => '> 320px', 'md' => '> 480px', 'lg' => '> 720px', 'xl' => '> 960px'];
    }
    
    protected function _getWidthOptions()
    {
        return [
            2 => '1/6',
            3 => '1/4',
            4 => '1/3',
            5 => '5/12',
            6 => '1/2',
            7 => '7/12',
            8 => '2/3',
            9 => '3/4',
            10 => '5/6',
            12 => __('Full width', 'directories'),
        ];
    }
    
    public function displayElementAdminAttr(Bundle $bundle, array $settings)
    {
        if ($settings['width'] !== 'responsive') {
            $width = $settings['width'];
            $grow = 0;
        } else {
            $width = 12;
            foreach (['xl', 'lg', 'md', 'sm', 'xs'] as $key) {
                if (!empty($settings['responsive'][$key]['width'])
                    && $settings['responsive'][$key]['width'] !== 'inherit'
                ) {
                    $width = $settings['responsive'][$key]['width'];
                    
                    break;
                }
            }
            $grow = empty($settings['responsive']['grow']) ? 0 : 1;
        }
        $width = (100 * $width / 12) . '%';
        return [
            // min-width:0 for firefox bug with flex items: https://github.com/philipwalton/flexbugs/issues/39
            'style' => 'flex:' . $grow . ' 0 ' . $width . ';width:' . $width . ';min-width:0;',
            'data-element-width' => $width,
        ];
    }
    
    public function displayElementRender(Bundle $bundle, array $element, $var)
    {
        $force_output = isset($element['settings']['hide_empty']) && empty($element['settings']['hide_empty']);
        if (!$html = $this->_renderChildren($bundle, $element['children'], $var)) {
            if (!$force_output) return;

            $html = '';
        } else {
            $html = implode(PHP_EOL, $html);
        }
        
        $class = '';
        if ($element['settings']['width'] !== 'responsive') {
            $width = (int)$element['settings']['width'];
            $class .= ' drts-col-' . $width;
        } else {
            $is_hidden = false;
            $grow = !empty($element['settings']['responsive']['grow']);
            unset($element['settings']['responsive']['grow']);
            foreach ($element['settings']['responsive'] as $key => $width) {
                if (!isset($width['width'])
                    || !strlen($width['width'])
                    || $width['width'] === 'inherit'
                ) continue;

                if (!$_width = intval($width['width'])) {
                    $is_hidden = true;
                    $class .= ' drts-' . ($key === 'xs' ? 'd-none' : $key . '-d-none');
                } else {
                    if ($is_hidden) {
                        $class .= ' drts-' . $key . '-d-block';
                        $is_hidden = false;
                    }
                    $class .= ' drts-col-' . ($key === 'xs' ? $_width : $key . '-' . $_width);
                    if ($grow) {
                        $class .= ' drts-' . ($key === 'xs' ? 'grow' : $key . '-grow');
                    }
                }
            }
        }
        
        return [
            'class' => $class,
            'html' => $html,
            'force_output' => $force_output,
        ];
    }

    protected function _displayElementReadableInfo(Bundle $bundle, Element $element)
    {
        $settings = $element->data['settings'];
        $ret = [
            'width' => [
                'label' => __('Column width', 'directories'),
            ],
        ];
        $widths = $this->_getWidthOptions();
        if ($settings['width'] === 'responsive') {
            $ret['width']['value'] = __('Responsive', 'directories');
            $widths[0] = __('Hidden', 'directories');
            $widths['inherit'] = __('Inherit from smaller', 'directories');
            foreach ($this->_getResponsiveWidthOptions() as $key => $title) {
                $setting = $settings['responsive'][$key];
                if (isset($setting['width'])) {
                    $value = $this->_application->H($widths[$setting['width']]);
                } else {
                    $value = __('Inherit from smaller', 'directories');
                }
                $ret['responsive-' . $key] = [
                    'label' => $title,
                    'value' => $value,
                    'is_html' => true,
                ];
            }
            $ret['responsive-grow'] = [
                'label' => __('Stretch to fill space', 'directories'),
                'value' => !empty($settings['responsive']['grow']),
                'is_bool' => true,
            ];
        } else {
            $ret['width']['value'] = $widths[$settings['width']];
        }

        return ['settings' => ['value' => $ret]];
    }
}