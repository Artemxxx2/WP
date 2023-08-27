<?php
namespace SabaiApps\Directories\Component\Location;

interface IGeocodingApis
{
    public function locationGetGeocodingApiNames();
    public function locationGetGeocodingApi($name);
}