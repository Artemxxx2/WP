<?php
namespace SabaiApps\Directories\Component\Search\Field;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Component\Entity\Model\Bundle;

abstract class AbstractField implements IField
{    
    protected $_application, $_name, $_info;

    public function __construct(Application $application, $name)
    {
        $this->_application = $application;
        $this->_name = $name;
    }

    public function searchFieldInfo($key = null)
    {
        if (!isset($this->_info)) {
            $this->_info = (array)$this->_searchFieldInfo();
        }

        return isset($key) ? (isset($this->_info[$key]) ? $this->_info[$key] : null) : $this->_info;
    }
    
    public function searchFieldSupports(Bundle $bundle)
    {
        return true;
    }
    
    public function searchFieldSettingsForm(Bundle $bundle, array $settings, array $parents = []){}
    
    public function searchFieldIsSearchable(Bundle $bundle, array $settings, &$value, array $requests = null)
    {
        return true;
    }

    public function searchFieldUnsearchableLabel(Bundle $bundle, array $settings, $value){}

    abstract protected function _searchFieldInfo();
}
