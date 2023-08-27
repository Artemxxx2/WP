<?php
namespace SabaiApps\Directories\Component\Location\Api;

class GoogleMapsGeocodingApi extends AbstractGoogleMapsApi implements IGeocodingApi
{
    protected function _doGetInfo()
    {
        return [
            'label' => __('Google Maps Geocoding', 'directories-pro'),
            'default_settings' => [
                'geocoding' => [
                    'country' => null,
                    'house_number_pos' => 'before',
                ],
            ],
        ];
    }

    public function locationApiLoad(array $settings)
    {
        $geocoding_settings = isset($settings['geocoding']) ? $settings['geocoding'] : [];
        $geocoding_settings['language'] = $this->_application->Map_Api_language();
        if (!empty($settings['api']['country'])) {
            $geocoding_settings['country'] = implode(',', $settings['api']['country']);
        } else {
            unset($geocoding_settings['country']);
        }
        $handle = $this->_load('geocoding', true);
        $this->_application->getPlatform()->addJsInline($handle, sprintf(
            'var DRTS_Location_googlemapsGeocoding = %s;',
            $this->_application->JsonEncode($geocoding_settings)
        ));
    }

    public function locationApiSettingsForm(array $settings, array $parents)
    {
        return [
            'geocoding' => [
                '#title' => __('Google Maps Geocoding', 'directories-pro'),
                '#class' => 'drts-form-label-lg',
                '#states' => [
                    'visible' => [
                        '[name="Map[lib][location_geocoding]"]' => ['type' => 'value' , 'value' => 'location_googlemaps'],
                    ],
                ],
                '#weight' => 10,
                'country' => [
                    '#title' => isset($title) ? $title : __('Country code', 'directories-pro'),
                    '#description' => __('Enter two-letter ISO 3166-1 Alpha-2 compatible country codes separated by commas to restrict geocoding results to specific countries.', 'directories-pro'),
                    '#type' => 'textfield',
                    '#default_value' => $settings['geocoding']['country'],
                    '#min_length' => 2,
                    '#max_length' => 2,
                    '#horizontal' => true,
                    '#placeholder' => 'US,JP',
                    '#alpha' => true,
                    '#separator' => ',',
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
            'address' => $address,
        ];
        if (!empty($settings['geocoding']['country'])) {
            $countries = [];
            foreach ((array)$settings['geocoding']['country'] as $country) {
               $countries[] = 'country:' . $country;
            }
            $params['components'] = implode('|', $countries);
        }
        return $this->_geocode('/geocode/json', $params, $settings);
    }

    public function locationApiReverseGeocode(array $latlng, array $settings)
    {
        return $this->_geocode('/geocode/json', [
            'latlng' => $latlng[0] . ',' . $latlng[1],
        ], $settings);
    }

    protected function _geocode($path, array $params, array $settings)
    {
        $params = [
            'key' => $settings['api']['key'],
            'language' => $this->_application->Map_Api_language(),
        ] + $params;
        $url = $this->_application->Map_GoogleMapsApi_url($path, $params);
        $geocode = $this->_application->Map_GoogleMapsApi_request($url);
        $geometry = $geocode->results[0]->geometry;
        return [
            'lat' => $geometry->location->lat,
            'lng' => $geometry->location->lng,
            'address' => $geocode->results[0]->formatted_address,
            'viewport' => [
                $geometry->viewport->southwest->lat,
                $geometry->viewport->southwest->lng,
                $geometry->viewport->northeast->lat,
                $geometry->viewport->northeast->lng,
            ],
        ] + $this->_getAddressComponents($geocode->results[0]->address_components, $settings);
    }

    protected function _getAddressComponents(array $components, array $settings)
    {
        $ret = ['street' => '', 'city' => '', 'province' => '', 'zip' => '', 'country' => ''];
        foreach ($components as $component) {
            foreach (array_keys($component->types) as $i) {
                switch ($component->types[$i]) {
                    case 'street_address':
                        $ret['street'] = $component->long_name;
                        break;
                    case 'locality':
                    case 'sublocality':
                        $ret['city'] = $component->long_name;
                        break;
                    case 'administrative_area_level_1':
                        $ret['province'] = $component->long_name;
                        break;
                    case 'postal_code':
                        $ret['zip'] = $component->long_name;
                        break;
                    case 'country':
                        $ret['country'] = strtoupper($component->short_name);
                        break;
                    case 'political':
                        break;
                    default:
                        $ret[$component->types[$i]] = $component->long_name;
                }
            }

            if (empty($ret['street'])) {
                if (isset($ret['route'])) {
                    if (isset($ret['street_number'])) {
                        if (isset($settings['geocoding']['house_number_pos'])
                            && $settings['geocoding']['house_number_pos'] === 'after'
                        ) {
                            $ret['street'] = $ret['route'] . ' ' . $ret['street_number'];
                        } else {
                            $ret['street'] = $ret['street_number'] . ' ' . $ret['route'];
                        }
                    } else {
                        $ret['street'] = $ret['route'];
                    }
                }
            }

            if (empty($ret['city'])) {
                if (!empty($ret['administrative_area_level_3'])) {
                    $ret['city'] = $ret['administrative_area_level_3'];
                } elseif (!empty($ret['administrative_area_level_2'])) {
                    $ret['city'] = $ret['administrative_area_level_2'];
                }
            }
        }
        return $ret;
    }
}