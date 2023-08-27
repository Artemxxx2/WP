<?php
namespace SabaiApps\Directories\Component\Dashboard\DisplayButton;

use SabaiApps\Directories\Component\Display;
use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Application;

abstract class AbstractPostDisplayButton extends Display\Button\AbstractButton
{    
    protected $_route, $_modalDanger, $_allowGuest;
    
    public function __construct(Application $application, $name, $route, $modalDanger = false, $allowGuest = false)
    {
        parent::__construct($application, $name);
        $this->_route = $route;
        $this->_modalDanger = (bool)$modalDanger;
        $this->_allowGuest = (bool)$allowGuest;
    }
    
    public function displayButtonLink(Entity\Model\Bundle $bundle, Entity\Type\IEntity $entity, array $settings, $displayName)
    {
        if (!$this->_application->Entity_IsRoutable($bundle, $this->_route, $entity)) return;
        
        if ($this->_application->getUser()->isAnonymous()) {
            if (!$this->_allowGuest) return;
            
            return; // @todo create pages for guest users to edit/delete posts
        }

        $label = $this->_getLabel($bundle, $entity, $settings);
        $options = [];
        if (!empty($settings['_icon'])) {
            $options['icon'] = 'fa-fw ' . $settings['_icon'];
        }
        $attr = [
            'class' => $settings['_class'],
            'style' => $settings['_style'],
            'data-modal-title' => $label . ' - ' . $this->_application->Entity_Title($entity),
            'data-modal-danger' => empty($this->_modalDanger) ? 0 : 1,
        ];
        if ($this->_route !== 'edit') {
            $this->_application->getPlatform()->addJsFile('form.min.js', 'drts-form', array('drts')); // for modal form
            $options['container'] = 'modal';
        }
        
        $link = $this->_application->LinkTo(
            $label,
            $url = $this->_getUrl($bundle, $entity, $settings, $displayName),
            $options,
            $attr
        );
        return $this->_application->Filter('dashboard_post_display_button_link', $link, [$entity, $displayName]);
    }
    
    protected function _getUrl(Entity\Model\Bundle $bundle, Entity\Type\IEntity $entity, array $settings, $displayName)
    {
        $params = [];
        if ($displayName === 'dashboard_row') {
            $params['from_dashboard'] = 1;
        }

        $path = $this->_route === 'edit' ? '/' : '/' . $this->_route;

        return $this->_application->getComponent('Dashboard')->getPostsPanelUrl($entity, '/posts/' . $entity->getId() . $path, $params, true);
    }
    
    protected function _getLabel(Entity\Model\Bundle $bundle, Entity\Type\IEntity $entity, array $settings)
    {
        return $settings['_label'];
    }
}