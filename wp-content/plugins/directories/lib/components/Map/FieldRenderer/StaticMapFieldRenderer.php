<?php
namespace SabaiApps\Directories\Component\Map\FieldRenderer;

use SabaiApps\Directories\Component\Field\IField;
use SabaiApps\Directories\Component\Entity\Type\IEntity;
use SabaiApps\Directories\Component\Field\Renderer\AbstractRenderer;
use SabaiApps\Directories\Component\Map\Api\GoogleMapsApi;

class StaticMapFieldRenderer extends AbstractRenderer
{
    protected function _fieldRendererInfo()
    {
        return [
            'label' => __('Static map renderer', 'directories'),
            'field_types' => ['map_map', 'location_address'],
            'default_settings' => [
                'width' => 200,
                'height' => 200,
                'high_res' => true,
                'marker_size' => 'mid',
                'marker_color' => '#FF0000',
            ],
            'separatable' => false,
            'accept_multiple' => true,
        ];
    }

    protected function _fieldRendererSettingsForm(IField $field, array $settings, array $parents = [])
    {
        $form = [
            'width' => [
                '#type' => 'slider',
                '#integer' => true,
                '#field_suffix' => 'px',
                '#min_value' => 100,
                '#max_value' => 1000,
                '#default_value' => $settings['height'],
                '#title' => __('Map width', 'directories'),
            ],
            'height' => [
                '#type' => 'slider',
                '#integer' => true,
                '#field_suffix' => 'px',
                '#min_value' => 100,
                '#max_value' => 1000,
                '#default_value' => $settings['height'],
                '#title' => __('Map height', 'directories'),
            ],
            'high_res' => [
                '#type' => 'checkbox',
                '#default_value' => !empty($settings['high_res']),
                '#title' => __('Use high resolution image', 'directories'),
            ],
            'marker_size' => [
                '#type' => 'select',
                '#title' => __('Marker size', 'directories'),
                '#options' => [
                    'tiny' => __('X-Small', 'directories'),
                    'small' => __('Small', 'directories'),
                    'mid' => __('Medium', 'directories'),
                    'normal' => __('Large', 'directories'),
                ],
                '#default_value' => $settings['marker_size'],
            ],
            'marker_color' => [
                '#type' => 'colorpicker',
                '#title' => __('Marker color', 'directories'),
                '#default_value' => $settings['marker_color'],
            ],
        ];

        return $form;
    }

    protected function _fieldRendererRenderField(IField $field, array &$settings, IEntity $entity, array $values, $more = 0)
    {
        if (!$map_api = $this->_application->Map_Api()) return;

        if (!$map_api instanceof GoogleMapsApi) {
            return '<div class="' . DRTS_BS_PREFIX . 'alert ' . DRTS_BS_PREFIX . 'alert-danger ">' . __('Static map is available with Google Maps only.', 'directories') . '</div>';
        }

        $config = $this->_application->getComponent('Map')->getConfig();
        if (!isset($config['lib']['api']['googlemaps']['key'])) return;

        $markers = [
            'size:' . (empty($settings['marker_size']) ? 'normal' : $this->_application->H($settings['marker_size'])),
            'color:0x' . (empty($settings['marker_color']) ? 'red' : $this->_application->H(substr($settings['marker_color'], 1))),
        ];
        foreach ($values as $value) {
            $markers[] = $value['lat'] . ',' . $value['lng'];
        }

        return sprintf(
            '<img src="https://maps.googleapis.com/maps/api/staticmap?%1$ssize=%2$dx%3$d&key=%4$s&markers=%5$s&scale=%6$d" width="%2$d" height="%3$d" alt="" />',
            count($markers) === 1 ? 'zoom=' . (int)$config['map']['default_zoom'] . '&' : '',
            $settings['width'],
            $settings['height'],
            $this->_application->H($config['lib']['api']['googlemaps']['key']),
            implode('%7C', $markers),
            empty($settings['high_res']) ? 1 : 2
        );
    }
}
