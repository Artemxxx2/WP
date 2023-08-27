<?php
namespace SabaiApps\Directories\Component\WordPressContent\LocationApi;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Exception\RuntimeException;
use SabaiApps\Directories\Component\Location\Api\IGeolocationApi;

class GeoIpGeolocationApi implements IGeolocationApi
{
    protected $_application, $_name, $_info;

    public function __construct(Application $application, $name)
    {
        $this->_application = $application;
        $this->_name = $name;
        $this->_info = [
            'label' => __('WordPress Geolocation IP Detection plugin', 'directories'),
            'default_settings' => [],
        ];
    }

    public function locationApiInfo($key = null)
    {
        return isset($key) ? (isset($this->_info[$key]) ? $this->_info[$key] : null) : $this->_info;
    }

    public function locationApiSettingsForm(array $settings, array $parents)
    {

    }

    public function locationApiLoad(array $settings)
    {

    }

    public function locationApiGeolocateIp($ip, array $settings)
    {
        if (!function_exists('geoip_detect2_get_info_from_ip')) {
            throw new RuntimeException('Geolocation function geoip_detect2_get_info_from_ip not found.');
        }
        $result = geoip_detect2_get_info_from_ip($ip);
        if (!$result->location->latitude
            || !$result->location->longitude
        ) {
            throw new RuntimeException('Failed fetching geo info from IP: ' . $ip);
        }
        return [
            'lat' => $result->location->latitude,
            'lng' => $result->location->longitude,
        ];
    }
}