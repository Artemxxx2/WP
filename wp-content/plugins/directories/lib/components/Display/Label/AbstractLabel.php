<?php
namespace SabaiApps\Directories\Component\Display\Label;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Component\Entity;

abstract class AbstractLabel implements ILabel
{
    protected $_application, $_name, $_info;

    public function __construct(Application $application, $name)
    {
        $this->_application = $application;
        $this->_name = $name;
    }

    public function displayLabelInfo(Entity\Model\Bundle $bundle, $key = null)
    {
        if (!isset($this->_info)) {
            $this->_info = (array)$this->_displayLabelInfo($bundle);
        }

        return isset($key) ? (isset($this->_info[$key]) ? $this->_info[$key] : null) : $this->_info;
    }
    
    public function displayLabelSettingsForm(Entity\Model\Bundle $bundle, array $settings, array $parents = []){}

    abstract protected function _displayLabelInfo(Entity\Model\Bundle $bundle);
}