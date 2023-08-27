<?php
namespace SabaiApps\Directories\Component\Field\Filter;

use SabaiApps\Directories\Component\Field\IField;
use SabaiApps\Directories\Application;

abstract class AbstractFilter implements IFilter
{
    protected $_application, $_name, $_info;

    public function __construct(Application $application, $name)
    {
        $this->_application = $application;
        $this->_name = $name;
    }

    public function fieldFilterInfo($key = null)
    {
        if (!isset($this->_info)) {
            $this->_info = (array)$this->_fieldFilterInfo();
        }

        return isset($key) ? (isset($this->_info[$key]) ? $this->_info[$key] : null) : $this->_info;
    }
    
    public function fieldFilterSettingsForm(IField $field, array $settings, array $parents = []){}
    
    public function fieldFilterSupports(IField $field)
    {
        return true;
    }
    
    public function fieldFilterLabels(IField $field, array $settings, $value, $form, $defaultLabel)
    {
        return;
    }

    abstract protected function _fieldFilterInfo();
}