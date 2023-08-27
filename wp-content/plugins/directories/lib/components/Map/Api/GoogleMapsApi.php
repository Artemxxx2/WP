<?php
namespace SabaiApps\Directories\Component\Map\Api;

class GoogleMapsApi extends AbstractApi
{
    protected function _mapApiInfo()
    {
        return [
            'label' => __('Google Maps', 'directories'),
            'default_settings' => [
                'key' => null,
                'no' => false,
            ],
            'default_map_settings' => [
                'type' => 'roadmap',
                'style' => '',
                'marker_cluster_color' => null,
            ],
            'privacy_policy' => [
                'url' => 'https://policies.google.com/privacy',
                'provider' => 'Google',
            ],
        ];
    }

    public function mapApiLoad(array $settings, array $mapSettings)
    {
        $platform = $this->_application->getPlatform();
        $googlemaps_handle = $this->_application->Map_GoogleMapsApi_load($settings);
        $js_deps = is_string($googlemaps_handle) ? [$googlemaps_handle] : [];
        $js_deps[] = 'drts-map-api';
        if (!empty($mapSettings['marker_clusters'])) {
            $platform->addJsFile('markerclusterer.min.js', 'markerclusterer', null, 'directories', true, true);
            $js_deps[] = 'markerclusterer';
        }
        $platform->addJsFile('map-googlemaps.min.js', 'drts-map-googlemaps', $js_deps, 'directories');
        if (!empty($mapSettings['style'])) {
            $platform->addJsFile($this->_mapStyle($mapSettings['style'], true), 'drts-map-googlemaps-style-' . str_replace(' ', '-', strtolower($mapSettings['style'])), 'drts-map-google-maps', false);
        }
        //if (!empty($mapSettings['marker_spiderify'])) {
        //    $platform->addJsFile('oms.min.js', 'spiderifier', null, 'directories', true, true);
        //}
    }

    public function mapApiSettingsForm(array $settings, array $parents)
    {
        return [
            '#title' => __('Google Maps API Settings (Browser)', 'directories'),
            '#class' => 'drts-form-label-lg',
            'key' => [
                '#type' => 'textfield',
                '#title' => __('API key', 'directories'),
                '#default_value' => $settings['key'],
                '#horizontal' => true,
                '#required' => function($form) {
                    return $form->getValue(['Map', 'lib', 'map']) === 'googlemaps'
                        || $form->getValue(['Map', 'lib', 'location_autocomplete']) === 'location_googlemaps'
                        || $form->getValue(['Map', 'lib', 'location_geocoding']) === 'location_googlemaps';
                },
            ],
            'no' => [
                '#type' => 'checkbox',
                '#title' => __('Do not load API', 'directories'),
                '#default_value' => !empty($settings['no']),
                '#horizontal' => true,
                '#description' => __('Enable this option if you are seeing a JavaScript API key conflict error with another application.', 'directories'),
            ],
        ];
    }

    public function mapApiMapSettingsForm(array $mapSettings, array $parents)
    {
        return [
            'type' => array(
                '#type' => 'select',
                '#title' => __('Default map type', 'directories'),
                '#options' => array(
                    'roadmap' => __('Google (roadmap)', 'directories'),
                    'satellite' => __('Google (satellite)', 'directories'),
                    'hybrid' => __('Google (hybrid)', 'directories'),
                ),
                '#default_value' => $mapSettings['type'],
                '#horizontal' => true,
                '#weight' => -1,
            ),
            'style' => array(
                '#type' => 'select',
                '#options' => array('' => __('Default', 'directories')) + $this->_mapStyle(),
                '#title' => __('Default map style', 'directories'),
                '#default_value' => $mapSettings['style'],
                '#horizontal' => true,
                '#weight' => 0,
            ),
            'marker_cluster_color' => array(
                '#type' => 'colorpicker',
                '#title' => __('Marker cluster color', 'directories'),
                '#default_value' => $mapSettings['marker_cluster_color'],
                '#states' => array(
                    'visible' => array(
                        sprintf('input[name="%s"]', $this->_application->Form_FieldName(array_merge($parents, ['marker_clusters']))) => ['type' => 'checked', 'value' => true],
                    ),
                ),
                '#horizontal' => true,
                '#weight' => 46,
            ),
        ];
    }

    protected function _mapStyle($style = null, $url = false)
    {
        if (!$styles = $this->_application->getPlatform()->getCache('map_api_styles')) {
            $styles = array('Red' => null, 'Blue' => null, 'Greyscale' => null, 'Night' => null, 'Sepia' => null, 'Chilled' => null,
                'Mixed' => null, 'Pale Dawn' => null, 'Apple Maps-esque' => null, 'Paper' => null, 'Hot Pink' => null, 'Flat Map' => null,
                'Subtle' => null, 'Light Monochrome' => null, 'Bright and Bubbly' => null, 'Clean Grey' => null, 'Subtle Greyscale' => null,
                'Light Dream' => null,
            );
            $styles = $this->_application->Filter('map_api_styles', $styles);
            ksort($styles);
            $this->_application->getPlatform()->setCache($styles, 'map_api_styles');
        }

        if (!isset($style)) return array_combine(array_keys($styles), array_keys($styles));

        $file = isset($styles[$style]) ? $styles[$style] : 'map-googlemaps-style-' . str_replace(' ', '-', strtolower($style)) . '.min.js';

        if (!$url) return $file;

        return strpos($file, 'http') === 0 ? $file : $this->_application->JsUrl($file, 'directories');
    }
}