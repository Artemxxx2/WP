<?php
namespace SabaiApps\Directories\Component\Location\MapApi;

use SabaiApps\Directories\Component\Map\Api\AbstractApi;

class LeafletMapApi extends AbstractApi
{
    protected function _mapApiInfo()
    {
        return [
            'label' => __('OpenStreetMap', 'directories-pro'),
            'default_settings' => [
            ],
            'default_map_settings' => [
                'use_custom_tile_url' => false,
                'tile_url' => null,
                'add_attribution' => true,
                'attribution' => null,
            ],
            'privacy_policy' => [
                'url' => 'https://wiki.osmfoundation.org/wiki/Privacy_Policy',
                'provider' => __('OpenStreetMap Foundation',  'directories-pro'),
            ],
        ];
    }

    public function mapApiLoad(array $settings, array $mapSettings)
    {
        $this->_application->getPlatform()
            ->addCssFile('leaflet.min.css', 'leaflet', null, 'directories-pro', null, true)
            ->addJsFile('leaflet.min.js', 'leaflet', null, 'directories-pro', true, true)
            ->addJsFile('bouncemarker.min.js', 'leaflet-bouncemarker', 'leaflet', 'directories-pro', true, true)
            ->addJsFile('location-leaflet-map.min.js', 'drts-location-leaflet-map', ['leaflet', 'drts-map-api'], 'directories-pro')
            ->addCssFile('location-leaflet.min.css', 'drts-location-leaflet', ['leaflet'], 'directories-pro');
        if (!empty($mapSettings['marker_clusters'])) {
            $this->_application->getPlatform()
                ->addCssFile('MarkerCluster.min.css', 'leaflet.markercluster', null, 'directories-pro', null, true)
                ->addCssFile('MarkerCluster.Default.min.css', 'leaflet.markercluster.default', null, 'directories-pro', null, true)
                ->addJsFile('leaflet.markercluster.min.js', 'leaflet.markercluster', 'leaflet', 'directories-pro', true, true);
        }
        if (!empty($mapSettings['gesture'])) {
            $this->_application->getPlatform()
                ->addCssFile('leaflet-gesture-handling.min.css', 'leaflet-gesture-handling', null, 'directories-pro', null, true)
                ->addJsFile('leaflet-gesture-handling.min.js', 'leaflet-gesture-handling', 'leaflet', 'directories-pro', true, true);
        }
    }

    public function mapApiMapSettingsForm(array $mapSettings, array $parents)
    {
        return [
            'gesture' => [
                '#type' => 'checkbox',
                '#title' => __('Enable gesture handling', 'directories-pro'),
                '#default_value' => !empty($mapSettings['gesture']),
                '#horizontal' => true,
                '#weight' => 55,
            ],
            'use_custom_tile_url' => [
                '#type' => 'checkbox',
                '#title' => __('Use custom tile URL', 'directories-pro'),
                '#default_value' => !empty($mapSettings['use_custom_tile_url']),
                '#horizontal' => true,
                '#weight' => 60,
            ],
            'tile_url' => [
                '#type' => 'textfield',
                '#default_value' => $mapSettings['tile_url'],
                '#horizontal' => true,
                '#states' => [
                    'visible' => [
                        sprintf('input[name="%s"]', $this->_application->Form_FieldName(array_merge($parents, ['use_custom_tile_url']))) => ['type' => 'checked', 'value' => true],
                    ],
                ],
                '#placeholder' => '//{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
                '#weight' => 61,
            ],
            'add_attribution' => [
                '#type' => 'checkbox',
                '#title' => __('Add map attribution', 'directories-pro'),
                '#default_value' => !empty($mapSettings['add_attribution']) || !isset($mapSettings['attribution']),
                '#horizontal' => true,
                '#weight' => 62,
            ],
            'attribution' => [
                '#type' => 'textfield',
                '#default_value' => isset($mapSettings['attribution']) ? $mapSettings['attribution'] : null,
                '#horizontal' => true,
                '#states' => [
                    'visible' => [
                        sprintf('input[name="%s"]', $this->_application->Form_FieldName(array_merge($parents, ['add_attribution']))) => ['type' => 'checked', 'value' => true],
                    ],
                ],
                '#placeholder' => '&amp;copy; <a href="https://www.openstreetmap.org/">OpenStreetMap</a> contributors',
                '#weight' => 63,
            ],
        ];
    }
}