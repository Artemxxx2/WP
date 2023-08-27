<?php
namespace SabaiApps\Directories\Component\View\DisplayElement;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Component\Display;
use SabaiApps\Directories\Component\Entity\Model\Bundle;
use SabaiApps\Directories\Component\Entity\Type\IEntity;

class ChildEntitiesDisplayElement extends AbstractEntitiesDisplayElement
{
    protected $_bundleType;

    public function __construct(Application $application, $name)
    {
        parent::__construct($application, $name);
        $this->_bundleType = substr($name, 20); // remove 'view_child_entities_' prefix
    }

    protected function _displayElementInfo(Bundle $bundle)
    {
        return parent::_displayElementInfo($bundle) + array(
            'label' => $label = $this->_application->Entity_Bundle($this->_bundleType, $bundle->component, $bundle->group)->getLabel(),
            'description' => sprintf(__('Displays %s of the current content', 'directories'), strtolower($label), $label),
        );
    }

    public function displayElementSupports(Bundle $bundle, Display\Model\Display $display)
    {
        return parent::displayElementSupports($bundle, $display)
            && $this->_application->Entity_Bundle($this->_bundleType, $bundle->component, $bundle->group); // make sure child bundle exists
    }

    public function displayElementRender(Bundle $bundle, array $element, $var)
    {
        if (($child_bundle = $this->_application->Entity_Bundle($this->_bundleType, $bundle->component, $bundle->group))
            && $this->_application->Entity_IsRoutable($child_bundle, 'list', $var)
        ) {
            return parent::displayElementRender($bundle, $element, $var);
        }
    }

    protected function _getEntitiesBundleType($entityOrBundle)
    {
        return $this->_bundleType;
    }

    protected function _getListEntitiesSettings(Bundle $bundle, array $element, IEntity $entity)
    {
        if (!$entity->isPublished()) return;

        return parent::_getListEntitiesSettings($bundle, $element, $entity);
    }

    protected function _getListEntitiesPath(Bundle $bundle, array $element, IEntity $entity)
    {
        return str_replace(':slug', $entity->getSlug(), $bundle->getPath(true));
    }
}
