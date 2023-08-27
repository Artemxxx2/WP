<?php
namespace SabaiApps\Directories\Component\System\Helper;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Form;

class UtilHelper
{   
    public function iconSizeOptions(Application $application)
    {
        return $application->Filter(
            'system_icon_size_options',
            [
                'sm' => __('Small', 'directories'),
                '' => __('Medium', 'directories'),
            ]
        );
    }
    
    public function iconSettingsForm(Application $application, Entity\Model\Bundle $bundle, array $settings, array $parents = [], $weight = null, $horizontal = true)
    {
        if (empty($bundle->info['entity_icon'])
            && empty($bundle->info['entity_image'])
        ) return [];

        $form = [
            'icon' => [
                '#type' => 'checkbox',
                '#title' => __('Show icon', 'directories'),
                '#default_value' => !empty($settings['icon']),
                '#weight' => isset($weight) ? $weight : null,
                '#horizontal' => $horizontal,
            ],
            'icon_settings' => [
                '#tree' => true,
                '#element_validate' => [
                    function (Form\Form $form, &$value) use ($bundle) {
                        $value['is_image'] = !empty($bundle->info['entity_image']);
                    }
                ],
                '#weight' => isset($weight) ? ++$weight : null,
                '#states' => [
                    'visible' => [
                        sprintf('input[name="%s"]', $application->Form_FieldName(array_merge($parents, ['icon']))) => [
                            'type' => 'checked', 
                            'value' => true,
                        ],
                    ],
                ],
                'size' => [
                    '#type' => 'select',
                    '#title' => __('Icon size', 'directories'),
                    '#default_value' => isset($settings['icon_settings']['size']) ? $settings['icon_settings']['size'] : null,
                    '#options' => $application->System_Util_iconSizeOptions(),
                    '#horizontal' => $horizontal,
                ],
                'fallback' => [
                    '#type' => 'checkbox',
                    '#title' => __('Fallback to default icon', 'directories'),
                    '#default_value' => !empty($settings['icon_settings']['fallback']),
                    '#states' => [
                        'invisible' => [
                            sprintf('[name="%s"]', $application->Form_FieldName(array_merge($parents, ['icon_settings', 'field']))) => [
                                'value' => '',
                            ],
                        ],
                    ],
                    '#horizontal' => $horizontal,
                ],
            ],
        ];

        if (empty($bundle->info['entity_image'])) {
            // Add color options
            $form['icon_settings']['color'] = $this->iconColorSettingsForm(
                $application,
                $bundle,
                isset($settings['icon_settings']['color']) && is_array($settings['icon_settings']['color']) ? $settings['icon_settings']['color'] : [],
                array_merge($parents, ['icon_settings', 'color']),
                $horizontal
            );
        }
        
        return $form;
    }

    public function iconColorSettingsForm(Application $application, Entity\Model\Bundle $bundle, array $settings, array $parents = [], $horizontal = true)
    {
        $color_field_options = $application->Entity_Field_options($bundle, [
            'interface' => 'Field\Type\ColorType',
            'prefix' => __('Field - ', 'directories'),
            'return_disabled' => true,
        ]);
        return [
            'type' => [
                '#type' => 'select',
                '#title' => __('Icon color', 'directories'),
                '#default_value' => isset($settings['type']) ? $settings['type'] : '',
                '#options' => $color_field_options[0] + [
                    '_custom' => __('Choose a color', 'directories'),
                    '' => __('Default', 'directories')
                ],
                '#options_disabled' => array_keys($color_field_options[1]),
                '#horizontal' => $horizontal,
            ],
            'custom' => [
                '#type' => 'colorpicker',
                '#default_value' => isset($settings['custom']) ? $settings['custom'] : null,
                '#states' => [
                    'visible' => [
                        sprintf('select[name="%s"]', $application->Form_FieldName(array_merge($parents, ['type']))) => [
                            'value' => '_custom',
                        ],
                    ],
                ],
                '#horizontal' => $horizontal,
            ],
        ];
    }
    
    public function iconSettingsToPermalinkOptions(Application $application, $bundle, array $settings)
    {
        if ((!$bundle = $application->Entity_Bundle($bundle))
            || empty($settings['icon'])
            || empty($settings['icon_settings'])
        ) return [];

        $options = [
            'icon' => empty($bundle->info['entity_image']) ? @$bundle->info['entity_icon'] : $bundle->info['entity_image'],
            'icon_is_image' => !empty($bundle->info['entity_image']) || !empty($bundle->info['entity_icon_is_image']),
            'icon_size' => $settings['icon_settings']['size'],
            'icon_fallback' => !empty($settings['icon_settings']['fallback']),
        ];
        if (!empty($settings['icon_settings']['color']['type'])) {
            $options['icon_color'] = $settings['icon_settings']['color']['type'] === '_custom'
                ? $settings['icon_settings']['color']['custom']
                : $settings['icon_settings']['color']['type'];
        }
        return $options;
    }
    
    public function cacheSettingsForm(Application $applicatoin, $value = null, array $possibleValues = null, array $exculeValues = null, $title = null)
    {
        $options = ['' => __('No cache', 'directories')];
        foreach ([1, 2, 5, 10, 30] as $min) {
            $options[$min * 60] = sprintf(_n('%d minute', '%d minutes', $min, 'directories'), $min);
        }
        foreach ([1, 2, 5, 10] as $hour) {
            $options[$hour * 3600] = sprintf(_n('%d hour', '%d hours', $hour, 'directories'), $hour);
        }
        foreach ([1, 2, 5, 10, 30] as $day) {
            $options[$day * 86400] = sprintf(_n('%d day', '%d days', $day, 'directories'), $day);
        }
        if (isset($possibleValues)) {
            $options = array_intersect_key($options, array_flip($possibleValues));
        }
        if (isset($exculeValues)) {
            $options = array_diff_key($options, array_flip($exculeValues));
        }
            
        return [
            '#title' => isset($title) ? $title : __('Cache output', 'directories'),
            '#type' => 'select',
            '#options' => $options,
            '#default_value' => $value,
            '#horizontal' => true,
        ];
    }
    
    public function colorOptions(Application $application, $buttons = false, $includeLink = false)
    {
        $ret = [];
        $colors = ['primary', 'secondary', 'info' , 'success', 'warning', 'danger', 'light', 'dark'];
        if ($buttons) {
            $btn_class = DRTS_BS_PREFIX . 'btn';
            foreach ($colors as $value) {
                $ret[$value] = sprintf('<button type="button" class="%1$s %1$s-sm %1$s-%2$s" onclick="return false;"> </button>', $btn_class, $value);
                $ret['outline-' . $value] = sprintf('<button type="button" class="%1$s %1$s-sm %1$s-outline-%2$s" onclick="return false;"> </button>', $btn_class, $value);
            }
            if ($includeLink) {
                $ret['link'] = __('Link', 'directories');
            }
        } else {
            foreach ($colors as $value) {
                $ret[$value] = '<span class="' . DRTS_BS_PREFIX. 'badge ' . DRTS_BS_PREFIX . 'badge-' . $value . '">&nbsp;</span>';
            }
        }
        
        return $ret;
    }
    
        
    public function colorSettingsForm(Application $application, $value = null, array $parents = [], $title = null)
    {
        return [
            'type' => array(
                '#type' => 'select',
                '#title' => isset($title) ? $title : __('Color', 'directories'),
                '#default_value' => isset($value['type']) ? $value['type'] : null,
                '#options' => ['' => __('Default', 'directories'), 'custom' => __('Custom', 'directories')],
                '#horizontal' => true,
            ),
            'value' => [
                '#type' => 'colorpicker',
                '#default_value' => isset($value['value']) ? $value['value'] : null,
                '#horizontal' => true,
                '#states' => array(
                    'visible' => array(
                        sprintf('select[name="%s"]', $application->Form_FieldName(array_merge($parents, ['type']))) => array('value' => 'custom'),
                    ),
                ),
            ],
        ];
    }

    public function strToBytes(Application $application, $str)
    {
        if (is_int($str)) return $str;

        $suffix = strtoupper(substr($str, -1));
        if (!in_array($suffix, ['P','T','G','M','K'])) return (int)$str;

        $value = (int)substr($str, 0, -1);
        switch ($suffix) {
            case 'P':
                $value *= 1024;
            case 'T':
                $value *= 1024;
            case 'G':
                $value *= 1024;
            case 'M':
                $value *= 1024;
            case 'K':
                $value *= 1024;
                break;
        }
        return $value;
    }

    public function bytesToStr(Application $application, $bytes, $decimals = 1)
    {
        $suffix = 'B';
        $suffix_list = ['PB','TB','GB','MB','KB'];
        while ($bytes > 1024
            && ($suffix = array_pop($suffix_list))
        ) {
            $bytes /= 1024;
        }
        return round($bytes, $decimals) . $suffix;
    }

    public function availableTags(Application $application, array $tags, $openTag = '', $closeTag = '')
    {
        return sprintf(
            $application->H(__('Available tags: %s', 'directories')),
            '<code>' . $openTag . implode($closeTag . '</code>, <code>' . $openTag, array_map([$application, 'H'], $tags)) . $closeTag . '</code>'
        );
    }

    public function sizeOptions(Application $application, $includeNegative = false)
    {
        $suffix = $includeNegative ? ' (+)' : '';
        $sizes = [
            0 => __('None', 'directories'),
            1 => __('X-Small', 'directories') . $suffix,
            2 => __('Small', 'directories') . $suffix,
            3 => __('Medium', 'directories') . $suffix,
            4 => __('Large', 'directories') . $suffix,
            5 => __('X-Large', 'directories') . $suffix,
        ];
        if ($includeNegative) {
            $sizes[-1] = __('X-Small', 'directories') . ' (-)';
            $sizes[-2] = __('Small', 'directories') . ' (-)';
            $sizes[-3] = __('Medium', 'directories') . ' (-)';
            $sizes[-4] = __('Large', 'directories') . ' (-)';
            $sizes[-5] = __('X-Large', 'directories') . ' (-)';
        }
        return $sizes;
    }

    public function hexToHsl(Application $application, $hex)
    {
        $rgb = $this->hexToRgb($application, $hex);
        $max = max($rgb);
        $min = min($rgb);
        $l = ($max + $min) / 2;

        if ($max === $min) {
            $h = $s = 0;
        } else {
            $diff = $max - $min;
            $s = $l > 0.5 ? $diff / (2 - $max - $min) : $diff / ($max + $min);
            switch($max) {
                case $rgb[0]:
                    $h = ($rgb[1] - $rgb[2]) / $diff + ($rgb[1] < $rgb[2] ? 6 : 0);
                    break;
                case $rgb[1]:
                    $h = ($rgb[2] - $rgb[0]) / $diff + 2;
                    break;
                case $rgb[2]:
                    $h = ($rgb[0] - $rgb[1]) / $diff + 4;
                    break;
            }
            $h /= 6;
        }

        return [$h, $s, $l];
    }

    public function hslToHex(Application $application, $hsl)
    {
        list($h, $s, $l) = $hsl;

        if ($s === 0) {
            $r = $g = $b = 1;
        } else {
            $q = $l < 0.5 ? $l * (1 + $s) : $l + $s - $l * $s;
            $p = 2 * $l - $q;

            $r = $this->hueToRgb($application, $p, $q, $h + 1 / 3);
            $g = $this->hueToRgb($application, $p, $q, $h);
            $b = $this->hueToRgb($application, $p, $q, $h - 1/3);
        }

        return $this->rgbToHex($application, [$r, $g, $b]);
    }

    public function hueToRgb(Application $application, $p, $q, $t)
    {
        if ($t < 0) $t += 1;
        if ($t > 1) $t -= 1;
        if ($t < 1 / 6) return $p + ($q - $p) * 6 * $t;
        if ($t < 1 / 2) return $q;
        if ($t < 2 / 3) return $p + ($q - $p) * (2 / 3 - $t) * 6;

        return $p;
    }

    public function hexToRgb(Application $application, $hex)
    {
        $hex = [$hex[0] . $hex[1], $hex[2] . $hex[3], $hex[4] . $hex[5]];
        return array_map(function ($part) {
            return hexdec($part) / 255;
        }, $hex);
    }

    public function rgbToHex(Application $application, $rgb)
    {
        $ret = '';
        foreach ((array)$rgb as $_rgb) {
            $ret .= str_pad(dechex($_rgb * 255), 2, '0', STR_PAD_LEFT);
        }
        return $ret;
    }

    public function btnClass(Application $application, array $options, $color = '', $primary = false)
    {
        $class = DRTS_BS_PREFIX . 'btn';
        if (isset($options['size'])) {
            $class .= ' ' . DRTS_BS_PREFIX . 'btn-' . $options['size'];
        }
        if (!isset($options['color'])) {
            $color = $this->btnColor($application, $color, $primary);
        } else {
            $color = $options['color'];
        }
        $class .= ' ' . DRTS_BS_PREFIX . 'btn-' . $color;

        return $class;
    }

    public function btnColor(Application $application, $color = '', $primary = false)
    {
        if ($primary) {
            switch ($color) {
                case 'primary':
                    return 'outline-light';
                default:
                    return 'outline-primary';
            }
        }
        switch ($color) {
            case 'primary':
            case 'secondary':
            case 'info':
            case 'success':
            case 'warning':
            case 'danger':
                return 'outline-light';
            default:
                return 'outline-secondary';
        }
    }
}