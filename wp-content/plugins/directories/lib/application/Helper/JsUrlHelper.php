<?php
namespace SabaiApps\Directories\Helper;

use SabaiApps\Directories\Application;

class JsUrlHelper
{
    public function help(Application $application, $file, $package = null)
    {       
        return $application->getPlatform()->getAssetsUrl($package) . '/js/' . $file;
    }
}