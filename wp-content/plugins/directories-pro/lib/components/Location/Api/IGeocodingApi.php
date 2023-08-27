<?php
namespace SabaiApps\Directories\Component\Location\Api;

interface IGeocodingApi extends IApi
{
    public function locationApiGeocode($address, array $settings);
    public function locationApiReverseGeocode(array $latlng, array $settings);
}