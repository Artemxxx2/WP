<?php
namespace SabaiApps\Directories\Helper;

use SabaiApps\Directories\Application;

class GridColumnSettingsFormHelper
{
    public function help(Application $application, $name, array $settings = [], array $parents = [], $label = null, array $columns = null, $responsive = true, $horizontal = true)
    {
        if (!isset($columns)) $columns = [2, 3, 4];
        $column_options = [];
        foreach ($columns as $num) {
            $column_options[$num] = sprintf(_n('%d column', '%d columns', $num, 'directories'), $num); 
        }
        $form = [
            $name => [
                '#type' => 'select',
                '#title' => isset($label) ? $label : __('Number of columns', 'directories'),
                '#options' => $responsive ? $column_options + ['responsive' => __('Responsive', 'directories')] : $column_options,
                '#default_value' => isset($settings[$name]) ? $settings[$name] : null,
                '#horizontal' => $horizontal,
            ],
        ];
        if ($responsive) {
            $column_options = [
                    1 => sprintf(__('%d column', 'directories'), 1),
                ] + $column_options;
            foreach (['xs' => '<= 320px', 'sm' => '> 320px', 'md' => '> 480px', 'lg' => '> 720px', 'xl' => '> 960px'] as $width => $width_label) {
                $form[$name . '_responsive'][$width] = [
                    '#field_prefix' => $width_label,
                    '#type' => 'select',
                    '#options' => $width !== 'xs' ? array('inherit' => __('Inherit from smaller', 'directories')) + $column_options : $column_options,
                    '#default_value' => isset($settings[$name . '_responsive'][$width]) ? $settings[$name . '_responsive'][$width] : 'inherit',
                    '#horizontal' => $horizontal,
                    '#states' => [
                        'visible' => [
                            sprintf('select[name="%s"]', $application->Form_FieldName(array_merge($parents, [$name]))) => ['value' => 'responsive'],
                        ],
                    ],
                ];
            }
        }
        
        return $form;
    }
}