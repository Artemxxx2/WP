<?php
namespace SabaiApps\Directories\Component\System\Helper;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Request;

class CookieHelper
{
    public function help(Application $application, $name, $value = null, $expire = 0, $httpOnly = false)
    {
        $name = $this->name($application, $name);
        if (isset($value)) {
            if (!setcookie($name, $value, $expire, $path = $application->getPlatform()->getCookiePath(), $domain = $application->getPlatform()->getCookieDomain(), false, $httpOnly)) {
                $application->logError('Failed setting cookie ' . $name . ' (Path: ' . $path . ', Domain: ' . $domain . ', Value: ' . $value . ').');
                return false;
            }
            return true;
        }
        return Request::cookie($name);
    }

    public function name(Application $application, $name)
    {
        return $name . '-' . $application->getPlatform()->getCookieHash();
    }
}