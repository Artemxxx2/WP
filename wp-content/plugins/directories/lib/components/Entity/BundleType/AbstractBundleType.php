<?php
namespace SabaiApps\Directories\Component\Entity\BundleType;

use SabaiApps\Directories\Application;

abstract class AbstractBundleType implements IBundleType
{    
    protected $_application, $_name, $_info;

    public function __construct(Application $application, $name)
    {
        $this->_application = $application;
        $this->_name = $name;
    }
    
    public function entityBundleTypeInfo($key = null)
    {
        if (!isset($this->_info)) {
            $this->_info = (array)$this->_entityBundleTypeInfo();
            if (!isset($this->_info['admin_path'])) {
                $this->_info['admin_path'] = '/directories/:directory_name/content_types/:bundle_name';
            }
        }

        return isset($key) ? (isset($this->_info[$key]) ? $this->_info[$key] : null) : $this->_info;
    }
    
    abstract protected function _entityBundleTypeInfo();
    
    public function entityBundleTypeSettingsForm(array $settings, array $parents = [])
    {
        return [];
    }
}