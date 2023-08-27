<?php
namespace SabaiApps\Directories\Component\Location\Helper;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Exception;
use SabaiApps\Framework\Application\HttpRequest;

class ApiHelper
{
    protected $_impls = [], $_loadedApis = [];

    public function help(Application $application, $type, $name = null, $load = false)
    {
        if (isset($this->_loadedApis[$type])
            && !$this->_loadedApis[$type]
        ) return;

        if ((!isset($name) && (!$name = $this->name($application, $type)))
            || (!$api = $this->impl($application, $name, $type, true))
        ) {
            $this->_loadedApis[$type] = false;
            return;
        }

        if ($load
            && !isset($this->_loadedApis[$type])
        ) {
            $api->locationApiLoad($this->settings($application, $name));
            $this->_loadedApis[$type] = true;
        }

        return $api;
    }

    public function components(Application $application, $type = null, $useCache = true)
    {
        if (!$useCache
            || (!$ret = $application->getPlatform()->getCache('location_api'))
        ) {
            $ret = [];
            foreach (['Autocomplete', 'Geocoding', 'Timezone', 'Places', 'Geolocation'] as $api) {
                foreach ($application->InstalledComponentsByInterface('Location\I' . $api . 'Apis') as $component_name) {
                    if (!$application->isComponentLoaded($component_name)) continue;

                    $method = 'locationGet' . $api . 'Api';
                    $names_method = $method . 'Names';
                    foreach ($application->getComponent($component_name)->$names_method() as $name) {
                        if (!$application->getComponent($component_name)->$method($name)) continue;

                        $ret[$api][$name] = $component_name;
                    }
                }
            }
            $application->getPlatform()->setCache($ret, 'location_api', 0);
        }

        return isset($type) ? $ret[$type] : $ret;
    }

    public function impl(Application $application, $name, $type, $returnFalse = false, $useCache = true)
    {
        if (!isset($this->_impls[$type][$name])) {
            if ((!$apis = $this->components($application, $type, $useCache))
                || !isset($apis[$name])
                || !$application->isComponentLoaded($apis[$name])
            ) {
                if ($returnFalse) return false;
                throw new Exception\UnexpectedValueException(sprintf('Invalid API: %s(%s)', $name, $type));
            }
            $method = 'locationGet' . $type . 'Api';
            $this->_impls[$type][$name] = $application->getComponent($apis[$name])->$method($name);
        }

        return $this->_impls[$type][$name];
    }

    public function options(Application $application, $type, $useCache = true)
    {
        $options = [];
        foreach ($this->components($application, $type, $useCache) as $name => $component) {
            if (!$application->isComponentLoaded($component)) continue;

            $method = 'locationGet' . $type . 'Api';
            if (!$api = $application->getComponent($component)->$method($name)) continue;
            $options[$name] = $api->locationApiInfo('label');
        }
        return $options;
    }

    /**
     * Loads Location API libraries
     * @param Application $application
     * @param array $options
     */
    public function load(Application $application, array $options = [])
    {
        $platform = $application->getPlatform()
            ->addJsFile('location-api.min.js', 'drts-location-api', 'drts', 'directories-pro')
            ->addJsInline('drts-location-api', sprintf(
                'DRTS_Location_apiErrors = %s;console.log(DRTS_Location_apiErrors["Geocoder returned no address components."])',
                $application->JsonEncode([
                    'Geocoder returned no address components.' => __('Geocoder returned no address components.', 'directories-pro'),
                    'Geocoder failed due to: %s' => __('Geocoder failed due to: %s', 'directories-pro'),
                ])
            ));


        if (!empty($options['location_map'])) {
            $platform->addJsFile('location-map.min.js', 'drts-location-map', 'drts', 'directories-pro');
            if (!empty($options['location_map_sticky'])) {
                $platform->addJsFile('jquery.sticky.min.js', 'jquery-jsticky', 'jquery', 'directories-pro')
                    ->loadImagesLoadedJs();
            }
        }

        if (!empty($options['location_field'])) {
            $options['map_field'] = true;
            $platform->addJsFile('location-field.min.js', 'drts-location-field', 'drts-map-field', 'directories-pro');
            $this->_loadApi($application, 'Geocoding');
            $this->_loadApi($application, 'Timezone');
        }

        if (!empty($options['location_textfield'])) {
            $platform->addJsFile('location-textfield.min.js', 'drts-location-textfield', 'drts', 'directories-pro');
            $this->_loadApi($application, 'Geocoding');
        }

        if (!empty($options['location_autocomplete'])) {
            $this->_loadApi($application, 'Autocomplete');
        }

        if (!empty($options['location_places'])) {
            $this->_loadApi($application, 'Places', 'location_googlemaps');
        }

        $application->Map_Api_load($options);
    }

    protected function _loadApi(Application $application, $type, $name = null)
    {
        $this->help($application, $type, $name, true);
    }

    public function name(Application $application, $type)
    {
        return $application->getComponent('Map')->getConfig('lib', 'location_' . strtolower($type));
    }

    public function settings(Application $application, $name)
    {
        return (array)$application->getComponent('Map')->getConfig('lib', 'api', $name);
    }

    public function geocode(Application $application, $address, $cache = true)
    {
        return $this->_doGeocode($application, $application->Filter('location_geocoding_address', trim($address), [$cache]), $cache);
    }

    public function reverseGeocode(Application $application, array $latlng, $cache = true)
    {
        return $this->_doGeocode($application, $latlng, $cache, true);
    }

    protected function _doGeocode(Application $application, $query, $cache = true, $isReverse = false)
    {
        $hash = md5(serialize([$query, $isReverse]));
        if (!$cache
            || (!$data = $application->getPlatform()->getCache($isReverse ? 'location_api_geocode_reverse' : 'location_api_geocode'))
            || !isset($data[$hash])
        ) {
            if (!$api_name = $this->name($application, 'Geocoding')) {
                throw new Exception\RuntimeException('No geocoding provider configured.');
            }

            $api = $this->impl($application, $api_name, 'Geocoding');
            $settings = $this->settings($application, $api_name);
            if ($isReverse) {
                $geocoded = $api->locationApiReverseGeocode($query, $settings);
            } else {
                $geocoded = $api->locationApiGeocode($query, $settings);
            }
            $geocoded = $application->Filter('location_geocoding_results', $geocoded, [$api_name, $query, $isReverse]);

            if (!$cache) return $geocoded;

            // Init cache
            if (!isset($data)
                || !is_array($data)
            ) {
                $data = [];
            } else {
                if (count($data) > 100) {
                    array_shift($data);
                }
            }
            // Append to cache
            $data[$hash] = $geocoded;
            $application->getPlatform()->setCache(
                $data,
                $isReverse ? 'location_api_geocode_reverse' : 'location_api_geocode',
                259200 // cache max 30 days
            );
        }
        return $data[$hash];
    }

    public function timezone(Application $application, array $latlng)
    {
        if (!$name = $this->name($application, 'Timezone')) {
            throw new Exception\RuntimeException('No timezone API configured.');
        }

        return $this->impl($application, $name, 'Timezone')
            ->locationApiGetTimezone($latlng, $this->settings($application, $name));
    }

    public function placeRating(Application $application, $placeId, $name = null)
    {
        if (!isset($name)
            && (!$name = $this->name($application, 'Places'))
        ) {
            throw new Exception\RuntimeException('No places API configured.');
        }

        return $this->impl($application, $name, 'Places')
            ->locationApiGetPlaceRating($placeId, $this->settings($application, $name));
    }

    public function viewport(Application $application, $latitude, $longitude, $distance = 20)
    {
        $distance_unit = $application->getComponent('Map')->getConfig('map', 'distance_unit');
        $radius = $distance_unit === 'mi' ? 3959 : 6371;
        //	Get SW lat/lng
        $sw_lat = rad2deg(asin(sin(deg2rad($latitude)) * cos($distance / $radius) + cos(deg2rad($latitude)) * sin($distance / $radius) * cos(deg2rad(225))));
        $sw_lng = rad2deg(deg2rad($longitude) + atan2(sin(deg2rad(225)) * sin($distance / $radius) * cos(deg2rad($latitude)), cos($distance / $radius) - sin(deg2rad($latitude)) * sin(deg2rad($sw_lat))));
        //	Get NE lat/lng
        $ne_lat = rad2deg(asin(sin(deg2rad($latitude)) * cos($distance / $radius) + cos(deg2rad($latitude)) * sin($distance / $radius) * cos(deg2rad(45))));
        $ne_lng = rad2deg(deg2rad($longitude) + atan2(sin(deg2rad(45)) * sin($distance / $radius) * cos(deg2rad($latitude)), cos($distance / $radius) - sin(deg2rad($latitude)) * sin(deg2rad($ne_lat))));

        return [$sw_lat, $sw_lng, $ne_lat, $ne_lng];
    }

    public function geolocateIp(Application $application, $ip = null)
    {
        if (!$api_name = $this->name($application, 'Geolocation')) {
            throw new Exception\RuntimeException('No geolocation provider configured.');
        }
        $api = $this->impl($application, $api_name, 'Geolocation');
        if (empty($ip)) {
            if (!$ip = HttpRequest::ip()) {
                throw new Exception\RuntimeException('Could not fetch IP address.');
            }
        } else {
            if (!filter_var($ip, FILTER_VALIDATE_IP)) {
                throw new Exception\RuntimeException('Invalid IP address:' . $ip);
            }
        }
        if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            $application->logNotice('Skipping geolocation for private IP: ' . $ip);
            return;
        }
        $settings = $this->settings($application, $api_name);
        return $api->locationApiGeolocateIp($ip, $settings);
    }
}