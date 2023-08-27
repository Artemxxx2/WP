<?php
namespace SabaiApps\Directories\Component\Location\Api;

interface IGeolocationApi extends IApi
{
    public function locationApiGeolocateIp($ip, array $settings);
}