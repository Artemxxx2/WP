<?php
namespace SabaiApps\Directories\Helper;

use SabaiApps\Directories\Application;

class MainUrlHelper
{
    public function help(Application $application, $route = '/', array $params = [], $fragment = '', $separator = '&amp;', $forceTrailingSlash = false)
    {
        return $application->Url([
            'route' => $route,
            'script' => 'main',
            'params' => $params,
            'fragment' => $fragment,
            'separator' => $separator,
            'force_trailing_slash' => $forceTrailingSlash,
        ]);
    }
}