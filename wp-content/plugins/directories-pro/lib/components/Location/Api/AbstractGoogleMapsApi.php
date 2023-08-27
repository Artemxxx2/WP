<?php
namespace SabaiApps\Directories\Component\Location\Api;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Request;

abstract class AbstractGoogleMapsApi
{
    protected $_application, $_name, $_info;

    public function __construct(Application $application, $name)
    {
        $this->_application = $application;
        $this->_name = $name;
    }

    protected function _getInfo($key = null)
    {
        if (!isset($this->_info)) {
            $this->_info = $this->_doGetInfo();
        }
        return isset($key) ? (isset($this->_info[$key]) ? $this->_info[$key] : null) : $this->_info;
    }

    abstract protected function _doGetInfo();

    public function locationApiInfo($key = null)
    {
        return $this->_getInfo($key);
    }

    protected function _load($type, $loadUtil = false)
    {
        $googlemaps_handle = $this->_application->Map_GoogleMapsApi_load();
        $js_deps = is_string($googlemaps_handle) ? [$googlemaps_handle] : [];
        $js_deps[] = 'drts-location-api';
        $this->_application->getPlatform()->addJsFile(
            'location-googlemaps-' . $type . '.min.js',
            $handle = 'drts-location-googlemaps-' . $type,
            $js_deps,
            'directories-pro'
        );
        if ($loadUtil) {
            $this->_application->getPlatform()->addJsFile(
                'location-googlemaps-util.min.js',
                'drts-location-googlemaps-util',
                $js_deps,
                'directories-pro'
            );
        }
        $endpoint = $this->_application->Url('/_drts/location/api', [Request::PARAM_CONTENT_TYPE => 'json']);
        $this->_application->getPlatform()->addJsInline(
            $handle,
            "var DRTS_Location_googlemapsApiEndpoint = '" . $this->_application->H($endpoint) . "';"
        );
        return $handle;
    }

    public function locationApiSettingsForm(array $settings, array $parents)
    {
        return [
            'api' => [
                '#title' => __('Google Maps API Settings (Server)', 'directories-pro'),
                '#class' => 'drts-form-label-lg',
                '#states' => [
                    'visible_or' => [
                        '[name="Map[lib][location_geocoding]"]' => ['type' => 'value', 'value' => 'location_googlemaps'],
                        '[name="Map[lib][location_timezone]"]' => ['type' => 'value', 'value' => 'location_googlemaps'],
                    ],
                ],
                '#weight' => 1,
                'key' => [
                    '#type' => 'textfield',
                    '#title' => __('API key', 'directories-pro'),
                    '#default_value' => isset($settings['api']['key']) ? $settings['api']['key'] : null,
                    '#horizontal' => true,
                    '#required' => function($form) {
                        return $form->getValue(['Map', 'lib', 'location_geocoding']) === 'location_googlemaps'
                            || $form->getValue(['Map', 'lib', 'location_timezone']) === 'location_googlemaps';
                    },
                ],
            ],
        ];
    }
}