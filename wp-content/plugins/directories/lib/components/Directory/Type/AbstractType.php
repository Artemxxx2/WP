<?php
namespace SabaiApps\Directories\Component\Directory\Type;

use SabaiApps\Directories\Application;

abstract class AbstractType implements IType
{
    protected $_application, $_name, $_info, $_primaryContentType, $_primaryContentTypeName;

    public function __construct(Application $application, $name)
    {
        $this->_application = $application;
        $this->_name = $name;
    }

    public function directoryInfo($key = null)
    {
        if (!isset($this->_info)) {
            $this->_info = (array)$this->_directoryInfo();
        }

        return isset($key) ? (isset($this->_info[$key]) ? $this->_info[$key] : null) : $this->_info;
    }

    abstract protected function _directoryInfo();
}