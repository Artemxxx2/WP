<?php
namespace SabaiApps\Directories\Component\Location\Api;

interface ITimezoneApi extends IApi
{
    public function locationApiGetTimezone(array $latlng, array $settings);
}