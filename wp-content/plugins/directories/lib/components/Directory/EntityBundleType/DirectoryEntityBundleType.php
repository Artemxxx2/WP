<?php
namespace SabaiApps\Directories\Component\Directory\EntityBundleType;

use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Application;

class DirectoryEntityBundleType extends Entity\BundleType\AbstractBundleType
{
    protected $_directoryType, $_contentType;
    
    public function __construct(Application $application, $name)
    {
        parent::__construct($application, $name);
        $parts = explode('__', $name);
        $this->_directoryType = $parts[0];
        $this->_contentType = $parts[1];
    }
    
    protected function _entityBundleTypeInfo()
    {
        return $this->_application->Directory_Types_entityBundleTypeInfo($this->_directoryType, $this->_contentType);
    }
}
