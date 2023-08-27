<?php
namespace SabaiApps\Directories\Component\System\Tool;

use SabaiApps\Directories\Application;

abstract class AbstractTool implements ITool
{
    protected $_application, $_name;
    
    public function __construct(Application $application, $name)
    {
        $this->_application = $application;
        $this->_name = $name;
    }
    
    public function systemToolInfo($key = null)
    {
        if (!isset($this->_info)) {
            $this->_info = (array)$this->_systemToolInfo();
        }

        return isset($key) ? (isset($this->_info[$key]) ? $this->_info[$key] : null) : $this->_info;
    }

    public function systemToolSettingsForm(array $parents = []){}

    public function systemToolInit(array $settings, array &$storage, array &$logs)
    {
        return ['default' => 1];
    }

    abstract protected function _systemToolInfo();
}