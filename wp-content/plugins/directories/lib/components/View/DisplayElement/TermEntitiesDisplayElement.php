<?php
namespace SabaiApps\Directories\Component\View\DisplayElement;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Component\Display\Model\Display;
use SabaiApps\Directories\Component\Entity\Model\Bundle;
use SabaiApps\Directories\Component\Entity\Type\IEntity;

class TermEntitiesDisplayElement extends AbstractEntitiesDisplayElement
{
    protected $_contentBundleType;

    public function __construct(Application $application, $name)
    {
        parent::__construct($application, $name);
        $this->_contentBundleType = substr($name, 19); // remove 'view_term_entities_' prefix
    }

    protected function _displayElementInfo(Bundle $bundle)
    {
        return parent::_displayElementInfo($bundle) + array(
            'label' => $label = $this->_application->Entity_Bundle($this->_contentBundleType, $bundle->component, $bundle->group)->getLabel(),
            'description' => sprintf(__('Displays %s of the current term', 'directories'), strtolower($label), $label),
        );
    }

    public function displayElementSupports(Bundle $bundle, Display $display)
    {
        return parent::displayElementSupports($bundle, $display)
            && !empty($bundle->info['is_taxonomy']);
    }

    public function displayElementRender(Bundle $bundle, array $element, $var)
    {
        if (($content_bundle = $this->_application->Entity_Bundle($this->_contentBundleType, $bundle->component, $bundle->group))
            && $this->_application->Entity_IsRoutable($content_bundle, 'list', $var)
        ) {
            return parent::displayElementRender($bundle, $element, $var);
        }
    }

    protected function _getEntitiesBundleType($entityOrBundle)
    {
        return $this->_contentBundleType;
    }

    protected function _getListEntitiesSettings(Bundle $bundle, array $element, IEntity $entity)
    {
        $settings = parent::_getListEntitiesSettings($bundle, $element, $entity);
        $settings['settings']['query']['fields'][$entity->getBundleType()] = $entity->getId();
        return $settings;
    }
}
