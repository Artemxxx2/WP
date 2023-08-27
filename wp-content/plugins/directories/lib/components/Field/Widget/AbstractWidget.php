<?php
namespace SabaiApps\Directories\Component\Field\Widget;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Component\Entity;

abstract class AbstractWidget implements IWidget
{
    protected $_application, $_name, $_info;

    public function __construct(Application $application, $name)
    {
        $this->_application = $application;
        $this->_name = $name;
    }

    public function fieldWidgetInfo($key = null)
    {
        if (!isset($this->_info)) {
            $this->_info = (array)$this->_fieldWidgetInfo();
        }

        return isset($key) ? (isset($this->_info[$key]) ? $this->_info[$key] : null) : $this->_info;
    }
    
    public function fieldWidgetSettingsForm($fieldType, Entity\Model\Bundle $bundle, array $settings, array $parents = [], array $rootParents = []){}
    
    public function fieldWidgetSupports($fieldOrFieldType)
    {
        return true;
    }

    abstract protected function _fieldWidgetInfo();
}