<?php
namespace SabaiApps\Directories\Component\Location;

interface IPlacesApis
{
    public function locationGetPlacesApiNames();
    public function locationGetPlacesApi($name);
}