<?php
namespace SabaiApps\Directories\Component\Location;

interface IGeolocationApis
{
    public function locationGetGeolocationApiNames();
    public function locationGetGeolocationApi($name);
}