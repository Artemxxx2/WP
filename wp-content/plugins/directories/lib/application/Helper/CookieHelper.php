<?php
namespace SabaiApps\Directories\Helper;

use SabaiApps\Directories\Application;

class CookieHelper
{
    public function help(Application $application, $name, $value = null, $expire = 0, $httpOnly = false)
    {
        return $application->System_Cookie($name, $value, $expire, $httpOnly);
    }
}