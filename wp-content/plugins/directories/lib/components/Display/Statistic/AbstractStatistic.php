<?php
namespace SabaiApps\Directories\Component\Display\Statistic;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Component\Entity;

abstract class AbstractStatistic implements IStatistic
{
    protected $_application, $_name, $_info;

    public function __construct(Application $application, $name)
    {
        $this->_application = $application;
        $this->_name = $name;
    }

    public function displayStatisticInfo(Entity\Model\Bundle $bundle, $key = null)
    {
        if (!isset($this->_info)) {
            $this->_info = (array)$this->_displayStatisticInfo($bundle);
        }

        return isset($key) ? (isset($this->_info[$key]) ? $this->_info[$key] : null) : $this->_info;
    }
    
    public function displayStatisticSettingsForm(Entity\Model\Bundle $bundle, array $settings, array $parents = [], $type = 'icon'){}

    abstract protected function _displayStatisticInfo(Entity\Model\Bundle $bundle);
    
    public function displayStatisticIsPreRenderable(Entity\Model\Bundle $bundle, array $settings)
    {
        return false;
    }
    
    public function displayStatisticPreRender(Entity\Model\Bundle $bundle, array $settings, array $entities){}
}