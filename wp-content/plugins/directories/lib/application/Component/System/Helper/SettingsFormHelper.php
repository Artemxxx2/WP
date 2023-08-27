<?php
namespace SabaiApps\Directories\Component\System\Helper;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Component\Form\Form;

class SettingsFormHelper
{
    public function help(Application $application, $name, $settings, array &$form)
    {
        $form['fields'][$name . '_uninstall'] = [
            '#tab' => 'System',
            '#weight' => 11,
            '#component' => $name,
            '#title' => __('Uninstall Settings', 'directories'),
            'uninstall_remove_data' => [
                '#type' => 'checkbox',
                '#title' => __('Remove data', 'directories'),
                '#description' => __('Check this option to completely remove all data when the plugin is deleted.', 'directories'),
                '#default_value' => !empty($settings['uninstall_remove_data']),
                '#horizontal' => true,
            ],
        ];
        $form['fields'][$name . '_log'] = [
            '#tab' => 'System',
            '#weight' => 10,
            '#component' => $name,
            '#title' => __('Log Settings', 'directories'),
            'log_dir' => [
                '#type' => 'textfield',
                '#title' => __('Log file location', 'directories'),
                '#default_value' => isset($settings['log_dir']) ? $settings['log_dir'] : null,
                '#placeholder' => ($dir = $application->getPlatform()->getLogDir()) ? $dir : $application->getComponent('System')->getVarDir('logs'),
                '#description' => __('Enter the full path to a directory writeable by the server.', 'directories'),
                '#horizontal' => true,
                '#element_validate' => [function(Form $form, &$value, $element) {
                    if (!strlen($value = rtrim(trim($value), '/'))) return;
                    if (!is_dir($value)) {
                        $form->setError(__('The directory specified is not a valid directory.', 'directories'), $element);
                    } elseif (!is_writable($value)) {
                        $form->setError(__('The directory specified is not writeable by the server.', 'directories'), $element);
                    }
                }],
            ],
            'disable_error_log' => [
                '#type' => 'checkbox',
                '#title' => __('Disable error log', 'directories'),
                '#default_value' => !empty($settings['disable_error_log']),
                '#horizontal' => true,
            ],
        ];
        if ($application->getPlatform()->getLanguages()) {
            $form['fields'][$name . '_translations'] = [
                '#tab' => 'System',
                '#weight' => 9,
                '#component' => $name,
                '#title' => __('Translation Settings', 'directories'),
                'auto_reg_str' => [
                    '#type' => 'checkbox',
                    '#title' => __('Auto register strings for translation', 'directories'),
                    '#description' => __('Check this option to automatically register strings while pages are rendered. This feature may slow down your site significantly and is only intended for sites that are in development.', 'directories'),
                    '#default_value' => !empty($settings['auto_reg_str']),
                    '#horizontal' => true,
                ],
            ];
        }
        $form['tabs'][$name . '_appearance'] = [
            '#title' => __('Appearance', 'directories'),
            '#weight' => 80,
        ];
        $default_colors = [
            'primary' => '#467fcf',
            'secondary' => '#868e96',
        ];
        $form['fields'][$name . '_appearance'] = [
            '#tab' => $name . '_appearance',
            '#weight' => 9,
            '#component' => $name,
            '#submit' => [
                9 => [ // weight
                    function (Form $form) use ($application, $name, $settings) {
                        $file = $application->getPlatform()->getVarDir() . '/style.css';
                        $value = $form->getValue($name . '_appearance');
                        if ($value == [
                                'css' => isset($settings['css']) ? $settings['css'] : null,
                                'color' => isset($settings['color']) ? $settings['color'] : null,
                            ]) {
                            if (file_exists($file)) return; // no change and file exists so abort
                        }

                        $css = '';
                        if (strlen($value['css']['custom_css'])) {
                            $css .= $value['css']['custom_css'];
                        }
                        $colors = [];
                        foreach (['primary', 'secondary'] as $color) {
                            if (!empty($value['color'][$color])) {
                                $colors[$color] = $this->_getColors($application, $value['color'][$color]);
                            }
                        }
                        if (!empty($colors)) {
                            ob_start();
                            include __DIR__ . '/_style.php';
                            $css .= ob_get_clean();
                        }
                        if (strlen($css)) {
                            if (false === file_put_contents($file, $css)) {
                                $form->setError('Error writing into file: ' . $file . '. Make sure the file can be created and is writeable.');
                            }
                        } else {
                            @unlink($file);
                        }
                    },
                ],
            ],
            'color' => [
                '#weight' => 10,
                '#title' => __('Color Settings', 'directories'),
                '#element_validate' => [function(Form $form, &$value, $element) use ($default_colors) {
                    foreach (array_keys($default_colors) as $color_name) {
                        if (isset($value[$color_name])
                            && (empty($value[$color_name]) || $value[$color_name] === $default_colors[$color_name])
                        ) $value[$color_name] = null;
                    }
                    if (empty($value)) $value = null;
                }],
                'primary' => [
                    '#title' => __('Primary color', 'directories'),
                    '#type' => 'colorpicker',
                    '#horizontal' => true,
                    '#default_value' => empty($settings['color']['primary']) ? $default_colors['primary'] : $settings['color']['primary'],
                    '#attributes' => ['data-custom-colors' => $default_colors['primary']],
                ],
                'secondary' => [
                    '#title' => __('Secondary color', 'directories'),
                    '#type' => 'colorpicker',
                    '#horizontal' => true,
                    '#default_value' => empty($settings['color']['secondary']) ? $default_colors['secondary'] : $settings['color']['secondary'],
                    '#attributes' => ['data-custom-colors' => $default_colors['secondary']],
                ],
            ],
            'css' => [
                '#weight' => 50,
                '#title' => __('CSS Settings', 'directories'),
                'custom_css' => [
                    '#title' => __('Custom CSS', 'directories'),
                    '#type' => 'editor',
                    '#language' => 'css',
                    '#horizontal' => true,
                    '#default_value' => isset($settings['css']['custom_css']) ? $settings['css']['custom_css'] : null,
                ],
            ],
        ];
    }

    protected function _getColors(Application $application, $hex)
    {
        $hsl = $application->System_Util_hexToHsl($hex = substr($hex, 1));
        foreach ([-15, -12, -9, -7, 7, 12, 25] as $percent) {
            $_hex = $application->System_Util_hslToHex([$hsl[0], $hsl[1], ($hsl[2] * 100 + $percent) / 100]);
            if ($_hex === 'ffffff') $_hex = $hex;
            $ret[$percent] = '#' . $_hex;
            foreach ($application->System_Util_hexToRgb($_hex) as $_rgb) {
                $ret['rgb'][$percent][] = $_rgb * 255;
            }
        }
        $ret[0] = '#' . $hex;
        foreach ($application->System_Util_hexToRgb($hex) as $_rgb) {
            $ret['rgb'][0][] = $_rgb * 255;
        }

        return $ret;
    }
}