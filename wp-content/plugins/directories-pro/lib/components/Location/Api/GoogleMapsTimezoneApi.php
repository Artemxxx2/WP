<?php
namespace SabaiApps\Directories\Component\Location\Api;

use SabaiApps\Directories\Request;

class GoogleMapsTimezoneApi extends AbstractGoogleMapsApi implements ITimezoneApi
{
    protected function _doGetInfo()
    {
        return [
            'label' => __('Google Maps Time Zone', 'directories-pro'),
        ];
    }

    public function locationApiLoad(array $settings)
    {
        $this->_load('timezone');
    }

    public function locationApiGetTimezone(array $latlng, array $settings)
    {
        $url = $this->_application->Map_GoogleMapsApi_url('/timezone/json', [
            'key' => $settings['api']['key'],
            'timestamp' => time(),
            'location' => $latlng[0] . ',' . $latlng[1],
        ]);
        return $this->_application->Map_GoogleMapsApi_request($url)->timeZoneId;
    }
}