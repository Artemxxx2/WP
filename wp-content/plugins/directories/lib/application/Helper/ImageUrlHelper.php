<?php
namespace SabaiApps\Directories\Helper;

use SabaiApps\Directories\Application;

class ImageUrlHelper
{    
    protected $_images = [];
    
    public function help(Application $application, $file, $package = null)
    {
        if (isset($package)) {
            return $application->getPlatform()->getAssetsUrl($package) . '/images/' . $file;
        }
        
        if (!isset($this->_images[$file])) {
            $this->_images[$file] = $this->_getImageFileUrl($application, $file);
        }
        return $this->_images[$file];
    }
    
    protected function _getImageFileUrl(Application $application, $file)
    {
        foreach ($application->getPlatform()->getCustomAssetsDir() as $index => $custom_dir) {
            if (file_exists($custom_dir . '/' . $file)) {
                return $application->getPlatform()->getCustomAssetsDirUrl($index) . '/' . $file;
            }
        }
        return $application->getPlatform()->getAssetsUrl() . '/images/' . $file;
    }
}