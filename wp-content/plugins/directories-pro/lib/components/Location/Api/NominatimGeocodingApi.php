<?php
namespace SabaiApps\Directories\Component\Location\Api;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Exception;

class NominatimGeocodingApi implements IGeocodingApi
{
    protected $_application, $_name, $_info;

    public function __construct(Application $application, $name)
    {
        $this->_application = $application;
        $this->_name = $name;
        $this->_info = [
            'label' => __('OpenStreetMap Nominatim', 'directories-pro'),
            'default_settings' => [
                'geocoding' => [
                    'country' => null,
                    'house_number_pos' => 'before',
                ],
            ],
        ];
    }

    public function locationApiInfo($key = null)
    {
        return isset($key) ? (isset($this->_info[$key]) ? $this->_info[$key] : null) : $this->_info;
    }

    public function locationApiLoad(array $settings)
    {
        $geocoding_settings = isset($settings['geocoding']) ? $settings['geocoding'] : [];
        $geocoding_settings['language'] = $this->_application->Map_Api_language();
        $this->_application->getPlatform()
            ->addJsFile(
                'location-nominatim-geocoding.min.js',
                'drts-location-nominatim-geocoding',
                'drts-location-api',
                'directories-pro'
            )
            ->addJsInline(
                'drts-location-nominatim-geocoding',
                sprintf(
                    'var DRTS_Location_nominatimGeocoding = %s;',
                    $this->_application->JsonEncode($geocoding_settings)
                )
            );
    }

    public function locationApiSettingsForm(array $settings, array $parents)
    {
        return [
            'geocoding' => [
                '#title' => __('OpenStreetMap Nominatim', 'directories-pro'),
                '#class' => 'drts-form-label-lg',
                '#states' => [
                    'visible' => [
                        '[name="Map[lib][location_geocoding]"]' => ['type' => 'value' , 'value' => 'location_nominatim'],
                    ],
                ],
                '#weight' => 10,
                'country' => [
                    '#title' => __('Country code', 'directories-pro'),
                    '#description' => __('Enter two-letter ISO 3166-1 Alpha-2 compatible country codes separated by commas to restrict geocoding results to specific countries.', 'directories-pro'),
                    '#type' => 'textfield',
                    '#default_value' => $settings['geocoding']['country'],
                    '#min_length' => 2,
                    '#max_length' => 2,
                    '#horizontal' => true,
                    '#placeholder' => 'US,JP',
                    '#separator' => ',',
                    '#alpha' => true,
                ],
                'house_number_pos' => [
                    '#type' => 'select',
                    '#title' => __('House number position in geocoded address', 'directories-pro'),
                    '#options' => ['before' => __('Before street name', 'directories-pro'), 'after' => __('After street name', 'directories-pro')],
                    '#horizontal' => true,
                    '#default_value' => $settings['geocoding']['house_number_pos'],
                ],
            ],
        ];
    }

    public function locationApiGeocode($address, array $settings)
    {
        $params = [
            'format' => 'json',
            'limit' => 1,
            'addressdetails' => 1,
            'q' => $address,
            'accept-language' => $this->_application->Map_Api_language(),
        ];
        if (!empty($settings['country'])) {
            $params['countrycodes'] = strtolower(implode(',', $settings['country']));
        }
        $url = (string)$this->_application->Url([
            'script_url' => 'https://nominatim.openstreetmap.org/search',
            'params' => $params,
            'separator' => '&',
        ]);
        $results = $this->_sendRequest($url);
        if (empty($results[0])) {
            throw new Exception\RuntimeException('No geocoding results found. Request URL: ' . $url);
        }

        return $this->_parseResults($results[0], $settings);
    }

    public function locationApiReverseGeocode(array $latlng, array $settings)
    {
        $url = (string)$this->_application->Url([
            'script_url' => 'https://nominatim.openstreetmap.org/reverse',
            'params' => [
                'format' => 'json',
                'addressdetails' => 1,
                'zoom' => 18,
                'lat' => $latlng[0],
                'lon' => $latlng[1],
                'accept-language' => $this->_application->Map_Api_language(),
            ],
            'separator' => '&',
        ]);
        $results = $this->_sendRequest($url);
        if (empty($results)) {
            throw new Exception\RuntimeException('No geocoding results found. Request URL: ' . $url);
        }

        return $this->_parseResults($results, $settings);
    }

    protected function _parseResults(array $results, array $settings)
    {
        $ret = [
            'lat' => $results['lat'],
            'lng' => $results['lon'],
            'address' => $results['display_name'],
            'viewport' => [
                $results['boundingbox'][0],
                $results['boundingbox'][2],
                $results['boundingbox'][1],
                $results['boundingbox'][3],
            ],
        ];
        if (isset($results['address'])) {
            $ret += $this->_getAddressComponents($results['address'], $settings);
        }
        return $ret;
    }

    protected function _getAddressComponents(array $components, array $settings)
    {
        $ret = ['street' => '', 'city' => '', 'province' => '', 'zip' => '', 'country' => ''];
        foreach ($components as $type => $value) {
            switch ($type) {
                case 'road':
                    $ret['street'] = $value;
                    break;
                case 'city':
                    $ret['city'] = $value;
                    break;
                case 'state':
                    $ret['province'] = $value;
                    break;
                case 'postcode':
                    $ret['zip'] = $value;
                    break;
                case 'country_code':
                    $ret['country'] = strtoupper($value);
                    break;
                default:
                    $ret[$type] = $value;
            }
        }
        if (isset($ret['street']) && strlen($ret['street'])) {
            if (isset($ret['house_number'])) {
                if (isset($settings['geocoding']['house_number_pos'])
                    && $settings['geocoding']['house_number_pos'] === 'after'
                ) {
                    $ret['street'] = $ret['street'] . ' ' . $ret['house_number'];
                } else {
                    $ret['street'] = $ret['house_number'] . ' ' . $ret['street'];
                }
            }
        } else {
            if (isset($ret['suburb']) && strlen($ret['suburb'])) {
                $ret['street'] = $ret['suburb'];
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