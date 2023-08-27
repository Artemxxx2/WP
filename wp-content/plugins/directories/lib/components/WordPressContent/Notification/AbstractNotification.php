<?php
namespace SabaiApps\Directories\Component\WordPressContent\Notification;

use SabaiApps\Directories\Application;

abstract class AbstractNotification implements INotification
{
    protected $_application, $_name;

    public function __construct(Application $application, $name)
    {
        $this->_application = $application;
        $this->_name = $name;
    }
    
    public function wpNotificationInfo($key = null)
    {
        if (!isset($this->_info)) {
            $this->_info = (array)$this->_wpNotificationInfo();
        }
        return isset($key) ? (isset($this->_info[$key]) ? $this->_info[$key] : null) : $this->_info;
    }
    
    abstract protected function _wpNotificationInfo();
}