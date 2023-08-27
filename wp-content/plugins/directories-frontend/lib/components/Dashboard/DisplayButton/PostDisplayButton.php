<?php
namespace SabaiApps\Directories\Component\Dashboard\DisplayButton;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Exception;

class PostDisplayButton extends AbstractPostDisplayButton
{    
    public function __construct(Application $application, $name)
    {
        $modal_danger = $allow_guest = false;
        switch ($name) {
            case 'dashboard_posts_edit':
                $route = 'edit';
                $allow_guest = true;
                break;
            case 'dashboard_posts_delete':
                $route = 'delete';
                $modal_danger = $allow_guest = true;
                break;
            case 'dashboard_posts_submit':
                $route = 'submit';
                break;
            default:
                throw new Exception\InvalidArgumentException();
        }
        parent::__construct($application, $name, $route, $modal_danger, $allow_guest);
    }
    
    protected function _displayButtonInfo(Entity\Model\Bundle $bundle)
    {
        switch ($this->_name) {
            case 'dashboard_posts_edit':
                return array(
                    'label' => __('Edit post button', 'directories-frontend'),
                    'labellable' => false,
                    'default_settings' => array(
                        '_color' => 'outline-secondary',
                        '_icon' => 'fas fa-edit',
                    ),
                );
            case 'dashboard_posts_delete':
                return array(
                    'label' => __('Delete post button', 'directories-frontend'),
                    'labellable' => false,
                    'default_settings' => array(
                        '_color' => 'danger',
                        '_icon' => 'fas fa-trash-alt',
                    ),
                );
            case 'dashboard_posts_submit':
                return array(
                    'label' => __('Submit post button', 'directories-frontend'),
                    'labellable' => false,
                    'default_settings' => array(
                        '_color' => 'outline-secondary',
                        '_icon' => 'fas fa-plus',
                    ),
                    'overlayable' => false,
                );
        }
    }
    
    protected function _getLabel(Entity\Model\Bundle $bundle, Entity\Type\IEntity $entity, array $settings)
    {
        switch ($this->_name) {
            case 'dashboard_posts_edit':
                return __('Edit', 'directories-frontend');
            case 'dashboard_posts_delete':
                return __('Delete', 'directories-frontend');
            case 'dashboard_posts_submit':
                return __('Submit for review', 'directories-frontend');
        }
    }
}