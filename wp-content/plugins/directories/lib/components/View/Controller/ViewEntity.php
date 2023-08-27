<?php
namespace SabaiApps\Directories\Component\View\Controller;

use SabaiApps\Directories\Controller;
use SabaiApps\Directories\Context;
use SabaiApps\Directories\Component\Entity;

class ViewEntity extends Controller
{    
    protected function _doExecute(Context $context)
    {
        $entity = $this->_getEntity($context);
        // Render
        $context->setTitle($this->Entity_PageTitle($entity))
            ->addTemplate('view_entity')
            ->setAttributes(array(
                'entity' => $entity,
                'display' => $display = $this->_getDisplayName($context, $entity),
            ));
        // Invoke other components
        $this->Action('view_entity', array($entity, $display, $context));
    }
    
    protected function _getDisplayName(Context $context, Entity\Type\IEntity $entity)
    {
        if ($this->_isAmp($entity)) return 'amp_detailed';

        if (isset($context->settings['display'])
            && $context->settings['display'] !== 'detailed'
            && $this->Display_Display($entity, $context->settings['display']) // make sure the display exists
        ) {
            return $context->settings['display'];
        }
        return 'detailed';
    }
    
    protected function _isAmp(Entity\Type\IEntity $entity)
    {
        return !$entity->isTaxonomyTerm()
            && $this->getPlatform()->isAmpEnabled($entity->getBundleName())
            && $this->getPlatform()->isAmp();
    }
    
    protected function _getEntity(Context $context)
    {
        return $context->entity;
    }
}