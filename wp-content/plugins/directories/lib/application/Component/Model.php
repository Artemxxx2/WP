<?php
namespace SabaiApps\Directories\Component;

class Model extends \SabaiApps\Framework\Model\Model
{
    /**
     * @var SabaiApps\Directories\Application
     */
    private $_application;

    public function __construct(AbstractComponent $component)
    {
        parent::__construct(
            $component->getApplication()->getDB(),
            $component->getApplication()->getComponentPath($component->getName()) . '/Model',
            '\SabaiApps\Directories\Component\\' . $component->getName() . '\Model\\'
        );
        $this->_application = $component->getApplication();
    }

    public function __call($name, $args)
    {
        return $this->_application->callHelper($name, $args);
    }
    
    public function getComponentEntity($component, $entity, $entityId)
    {
        return $this->_application->getModel($entity, $component)->fetchById($entityId);
    }
    
    public function getComponentEntities($component, $entity, array $entityIds)
    {
        return $this->_application->getModel($entity, $component)->fetchByIds($entityIds);
    }
}