<?php
namespace SabaiApps\Directories\Component\Location;

interface ITimezoneApis
{
    public function locationGetTimezoneApiNames();
    public function locationGetTimezoneApi($name);
}