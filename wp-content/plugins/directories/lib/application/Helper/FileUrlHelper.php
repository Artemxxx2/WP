<?php
namespace SabaiApps\Directories\Helper;

use SabaiApps\Directories\Application;

class FileUrlHelper
{
    protected $_sitePath;
    
    public function help(Application $application, $file, $component = null)
    {     
        if (isset($component)) {
            $file = $application->getComponentPath($component) . '/' . $file;
        }
        $site_path = $this->_getSitePath($application);
        $site_url = $application->getPlatform()->getSiteUrl();
        return $site_path !== '/' // For sites with a slash only site path, for some reason
            ? str_replace($site_path, $site_url, $file)
            : $site_url . $file;
    }
    
    public function _getSitePath(Application $application)
    {
        if (!isset($this->_sitePath)) {
            $this->_sitePath = $application->getPath($application->getPlatform()->getSitePath());
        }
        return $this->_sitePath;
    }
}