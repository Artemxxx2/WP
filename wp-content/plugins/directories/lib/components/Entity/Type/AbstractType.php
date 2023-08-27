<?php
namespace SabaiApps\Directories\Component\Entity\Type;

use SabaiApps\Directories\Application;

abstract class AbstractType implements IType
{
    protected $_application, $_name, $_info;

    public function __construct(Application $application, $name)
    {
        $this->_application = $application;
        $this->_name = $name;
    }

    public function entityTypeInfo($key = null)
    {
        if (!isset($this->_info)) {
            $this->_info = $this->_entityTypeInfo();
        }

        return isset($key) ? (isset($this->_info[$key]) ? $this->_info[$key] : null) : $this->_info;
    }
    
    abstract protected function _entityTypeInfo();

    public function entityTypeEntityByTitle($bundleName, $title){}
    
    public function entityTypeGetQuery($operator = null, $bundleName = null)
    {
        return new Query($this->_application, $this->_name, $this->_getFieldQuery($operator), $bundleName);
    }
    
    protected function _getFieldQuery($operator)
    {
        return new FieldQuery($operator);
    }
}