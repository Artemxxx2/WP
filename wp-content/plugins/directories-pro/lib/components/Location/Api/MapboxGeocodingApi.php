<?php
namespace SabaiApps\Directories\Component\Location\Api;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Exception;

class MapboxGeocodingApi implements IGeocodingApi
{
    protected $_application, $_name, $_info;

    public function __construct(Application $application, $name)
    {
        $this->_application = $application;
        $this->_name = $name;
        $this->_info = [
            'label' => __('Mapbox Geocoding', 'directories-pro'),
            'default_settings' => [],
        ];
    }

    public function locationApiInfo($key = null)
    {
        return isset($key) ? (isset($this->_info[$key]) ? $this->_info[$key] : null) : $this->_info;
    }

    public function locationApiLoad(array $settings)
    {
        $geocoding_settings = [
            'accessToken' => $settings['api']['access_token'],
            'language' => $this->_application->Map_Api_language(),
            'country' => empty($settings['api']['country']) ? '' : implode(',', $settings['api']['country']),
        ];
        $this->_application->getPlatform()
            ->addJsFile(
                'location-mapbox-geocoding.min.js',
                'drts-location-mapbox-geocoding',
                'drts-location-api',
                'directories-pro'
            )
            ->addJsInline('drts-location-mapbox-geocoding', sprintf(
                'var DRTS_Location_mapboxGeocoding = %s;',
                $this->_application->JsonEncode($geocoding_settings)
            ));
    }

    public function locationApiSettingsForm(array $settings, array $parents)
    {
        return [
            'api' => [
                '#title' => __('Mapbox API Settings', 'directories-pro'),
                '#class' => 'drts-form-label-lg',
                '#states' => [
                    'visible_or' => [
                        '[name="Map[lib][location_geocoding]"]' => ['type' => 'value', 'value' => 'location_mapbox'],
                    ],
                ],
                '#weight' => 1,
                'access_token' => [
                    '#type' => 'textfield',
                    '#title' => __('Access token', 'directories-pro'),
                    '#default_value' => isset($settings['api']['access_token']) ? $settings['api']['access_token'] : null,
                    '#horizontal' => true,
                    '#description' => sprintf(
                        $this->_application->H(__('Visit the following page to create a Mapbox API account and an access token: %s', 'directories-pro')),
                        '<a href="https://www.mapbox.com/signup" target="_blank" rel="nofollow noopener">https://www.mapbox.com/signup</a>'
                    ),
                    '#description_no_escape' => true,
                    '#required' => function($form) {
                        return $form->getValue(['Map', 'lib', 'location_geocoding']) === $this->_name;
                    },
                ],
                'country' => [
                    '#title' => __('Country code', 'directories-pro'),
                    '#description' => __('Enter two-letter ISO 3166-1 Alpha-2 compatible country codes separated by commas to restrict geocoding results to specific countries.', 'directories-pro'),
                    '#type' => 'textfield',
                    '#default_value' => $settings['api']['country'],
                    '#min_length' => 2,
                    '#max_length' => 2,
                    '#horizontal' => true,
                    '#placeholder' => 'US,JP',
                    '#separator' => ',',
                    '#alpha' => true,
                ],
            ],
        ];
    }

    public function locationApiGeocode($address, array $settings)
    {
        $url = (string)$this->_application->Url([
            'script_url' => 'https://api.mapbox.com/geocoding/v5/mapbox.places/' . urlencode($address) . '.json',
            'params' => [
                'access_token' => $settings['api']['access_token'],
                'country' => empty($settings['api']['country']) ? '' : implode(',', $settings['api']['country']),
                'language' => $this->_application->Map_Api_language(),
                'limit' => 1,
            ],
            'separator' => '&',
        ]);
        $results = $this->_sendRequest($url);
        if (empty($results['features'][0])) {
            throw new Exception\RuntimeException('No geocoding results found. Request URL: ' . $url);
        }

        return $this->_parseResults($results['features'][0]);
    }

    public function locationApiReverseGeocode(array $latlng, array $settings)
    {
        $url = (string)$this->_application->Url([
            'script_url' => 'https://api.mapbox.com/geocoding/v5/mapbox.places/' . urlencode($latlng[1] . ',' . $latlng[0]) . '.json',
            'params' => [
                'access_token' => $settings['api']['access_token'],
                'country' => empty($settings['api']['country']) ? '' : implode(',', $settings['api']['country']),
                'language' => $this->_application->Map_Api_language(),
            ],
            'separator' => '&',
        ]);
        $results = $this->_sendRequest($url);
        if (empty($results['features'][0])) {
            throw new Exception\RuntimeException('No geocoding results found. Request URL: ' . $url);
        }

        return $this->_parseResults($results['features'][0]);
    }

    protected function _parseResults(array $results)
    {
        $ret = [
            'lat' => $results['center'][1],
            'lng' => $results['center'][0],
            'address' => $results['place_name'],
            'street' => implode(' ', [isset($results['address']) ? $results['address'] : '', isset($results['text']) ? $results['text'] : '']),
        ] + $this->_getAddressComponents(isset($results['context']) && is_array($results['context']) ? $results['context'] : []);
        // bbox does not seem to be returned always
        if (!empty($results['bbox'])) {
            $ret['viewport'] = [
                $results['bbox'][1],
                $results['bbox'][0],
                $results['bbox'][3],
                $results['bbox'][2],
            ];
        }
        return $ret;
    }

    protected function _getAddressComponents(array $components)
    {
        $ret = ['street' => '', 'city' => '', 'province' => '', 'zip' => '', 'country' => ''];
        foreach ($components as $component) {
            $component_name = substr($component['id'], 0, strpos($component['id'], '.'));
            switch ($component_name) {
                case 'place':
                    $ret['city'] = $component['text'];
                    break;
                case 'region':
                    $ret['province'] = $component['text'];
                    break;
                case 'postcode':
                    $ret['zip'] = $component['text'];
                    break;
                case 'country':
                    $ret['country'] = strtoupper($component['short_code']);
                    break;
                default:
                    $ret[$component_name] = $component['text'];
            }
        }
        return $ret;
    }

    protected function _sendRequest($url, $assoc = true)
    {
        $result = $this->_application->getPlatform()->remoteGet($url);
        if (null === $result = json_decode($result, $assoc)) {
            throw new Exception\RuntimeException('Failed parsing result returned from URL: ' . $url);
        }
        return $result;
    }
}