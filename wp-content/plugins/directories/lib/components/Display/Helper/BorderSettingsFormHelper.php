<?php
namespace SabaiApps\Directories\Component\Display\Helper;

use SabaiApps\Directories\Application;

class BorderSettingsFormHelper
{
    public function help(Application $application, array $values = [], array $parents = [])
    {
        $values += array(
            'style' => 'solid',
            'color' => '#999999',
            'secondary_color' => null,
            'width' => 5,
            'radius' => 5,
        );
        return array(
            '#tree' => true,
            'style' => array(
                '#title' => __('Border style', 'directories'),
                '#type' => 'select',
                '#options' => $this->styleOptions($application),
                '#horizontal' => true,
                '#default_value' => $values['style'],
            ),
            'color' => array(
                '#title' => __('Border color', 'directories'),
                '#horizontal' => true,
                '#type' => 'colorpicker',
                '#default_value' => $values['color'],
            ),
            'secondary_color' => array(
                '#horizontal' => true,
                '#type' => 'colorpicker',
                '#default_value' => $values['secondary_color'],
                '#states' => [
                    'invisible' => [
                        sprintf('[name="%s"]', $application->Form_FieldName(array_merge($parents, ['style']))) => ['value' => 'solid'],
                    ],
                ],
            ),
            'width' => array(
                '#title' => __('Border width', 'directories'),
                '#type' => 'slider',
                '#min_value' => 1,
                '#max_value' => 20,
                '#field_suffix' => 'px',
                '#horizontal' => true,
                '#default_value' => $values['width'],
                '#step' => 0.5,
                '#numeric' => true,
            ),
            'radius' => array(
                '#title' => __('Border radius', 'directories'),
                '#type' => 'slider',
                '#min_value' => 0,
                '#max_value' => 10,
                '#field_suffix' => 'px',
                '#horizontal' => true,
                '#default_value' => $values['radius'],
                '#step' => 0.5,
                '#numeric' => true,
            ),
        );
    }
    
    public function styleOptions(Application $application)
    {
        return [
            'solid' => __('Solid', 'directories'),
            'dashed' => __('Dashed', 'directories'),
            'dotted' => __('Dotted', 'directories'),
            'double' => __('Double', 'directories'),
            'gradient' => __('Gradient', 'directories'),
            '' => __('None', 'directories'),
        ];
    }
}