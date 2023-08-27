<?php
namespace SabaiApps\Directories\Component\Display\Button;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Component\Entity;

abstract class AbstractButton implements IButton
{
    protected $_application, $_name, $_info;

    public function __construct(Application $application, $name)
    {
        $this->_application = $application;
        $this->_name = $name;
    }

    public function displayButtonInfo(Entity\Model\Bundle $bundle, $key = null)
    {
        if (!isset($this->_info)) {
            $this->_info = (array)$this->_displayButtonInfo($bundle);
        }

        return isset($key) ? (isset($this->_info[$key]) ? $this->_info[$key] : null) : $this->_info;
    }
    
    public function displayButtonSettingsForm(Entity\Model\Bundle $bundle, array $settings, array $parents = []){}

    abstract protected function _displayButtonInfo(Entity\Model\Bundle $bundle);
    
    public function displayButtonIsPreRenderable(Entity\Model\Bundle $bundle, array $settings)
    {
        return false;
    }
    
    public function displayButtonPreRender(Entity\Model\Bundle $bundle, array $settings, array $entities){}
    
    protected function _getLoginButton($label, $redirect, $options, $attr)
    {
        $_attr = [
            'data-content' => sprintf(
                _x('You must %s to perform this action.', 'login', 'directories'),
                $this->_getLoginLink($redirect)
            ),
            'data-popover-title' => __('Login required', 'directories'),
        ];
        return $this->_application->LinkTo($label, '', array('btn' => true, 'container' => 'popover') + $options, $_attr + $attr);
    }
    
    protected function _getLoginLink($redirect)
    {
        return $this->_application->LinkTo(
            __('login', 'directories'),
            $this->_application->LoginUrl($redirect),
            [],
            ['rel' => 'nofollow']
        );
    }
}