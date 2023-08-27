<?php
namespace SabaiApps\Directories\Component\Map\Api;

use SabaiApps\Directories\Application;

abstract class AbstractApi implements IApi
{
    protected $_application, $_name, $_info;

    public function __construct(Application $application, $name)
    {
        $this->_application = $application;
        $this->_name = $name;
    }

    public function mapApiInfo($key = null)
    {
        if (!isset($this->_info)) {
            $this->_info = (array)$this->_mapApiInfo();
        }

        return isset($key) ? (isset($this->_info[$key]) ? $this->_info[$key] : null) : $this->_info;
    }

    public function mapApiSettingsForm(array $settings, array $parents){}

    public function mapApiMapSettingsForm(array $mapSettings, array $parents){}

    abstract protected function _mapApiInfo();
}