<?php
namespace SabaiApps\Directories\Component\Entity\DisplayStatistic;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Display;

class ChildEntityCountDisplayStatistic extends Display\Statistic\AbstractStatistic
{
    protected $_bundleType;
    
    public function __construct(Application $application, $name)
    {
        parent::__construct($application, $name);
        $this->_bundleType = substr($name, 26); // remove 'entity_child_entity_count_' prefix
    }
    
    protected function _displayStatisticInfo(Entity\Model\Bundle $bundle)
    {
        $child_bundle = $this->_application->Entity_Bundle($this->_bundleType, $bundle->component, $bundle->group);
        return array(
            'label' => sprintf(_x('%s count', 'number of item', 'directories'), $child_bundle->getLabel('singular'), strtolower($child_bundle->getLabel('singular'))),
            'default_settings' => array(
            ),
            'iconable' => false,
        );
    }
    
    public function displayStatisticRender(Entity\Model\Bundle $bundle, Entity\Type\IEntity $entity, array $settings)
    {
        $child_bundle = $this->_application->Entity_Bundle($this->_bundleType, $bundle->component, $bundle->group);
        return array(
            'number' => $number = isset($entity->entity_child_count[0][$this->_bundleType]) ? (int)$entity->entity_child_count[0][$this->_bundleType] : 0,
            'format' => $number === 1 ? $child_bundle->getLabel('count') : $child_bundle->getLabel('count2'),
            'icon' => $this->_application->Entity_BundleTypeInfo($child_bundle, 'icon'),
        );
    }
}