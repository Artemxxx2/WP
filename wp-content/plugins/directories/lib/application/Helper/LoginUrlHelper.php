<?php
namespace SabaiApps\Directories\Helper;

use SabaiApps\Directories\Application;

class LoginUrlHelper
{
    public function help(Application $application, $redirect = '')
    {
        $redirect = (string)$redirect;
        return $application->Filter('core_login_url', $application->getPlatform()->getLoginUrl($redirect), [$redirect]);
    }
}