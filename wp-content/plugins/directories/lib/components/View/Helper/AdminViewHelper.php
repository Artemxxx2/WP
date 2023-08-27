<?php
namespace SabaiApps\Directories\Component\View\Helper;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\View\Model;

class AdminViewHelper
{
    public function add(Application $application, Entity\Model\Bundle $bundle, $name, $mode, $label, array $settings, $default = false)
    {
        if ($this->exists($application, $bundle->name, $name)) return;
        
        $view = $application->getModel('View', 'View')->create();
        $view->bundle_name = $bundle->name;
        $view->name = $name;
        $view->mode = $mode;
        $view->data = array('label' => $label, 'settings' => $settings);
        $view->default = (bool)$default;
        $view->markNew()->commit();
                
        $application->Action('view_admin_view_added', array($view));
        
        return $view;
    }
    
    public function update(Application $application, Model\View $view, $name, $mode, $label, array $settings)
    {
        $view->name = $name;
        $view->mode = $mode;
        $view->data = array('label' => $label, 'settings' => $settings);
        $view->commit();
                
        $application->Action('view_admin_view_edited', array($view));
        
        return $view;
    }
    
    public function exists(Application $application, $bundleName, $name)
    {
        return $application->getModel('View', 'View')
            ->name_is($name)
            ->bundleName_is($bundleName)
            ->fetchOne();
    }
    
    public function setDefault(Application $application, Entity\Model\Bundle $bundle, Model\View $view, $force = false)
    {
        $commit = false;
        $current_views = $application->getModel('View', 'View')->bundleName_is($bundle->name)->default_is(true)->fetch();
        $current = $current_views->getNext();
        // Set other views as non-default since there should be only one default view
        while ($_view = $current_views->getNext()) {
            $_view->default = false;
            $commit = true;
        }
        if ($force
           || !$current // no default view set
        ) {
            if ($current) $current->default = false;
            $view->default = true;
            $commit = true;
        }
        if ($commit) $application->getModel(null, 'View')->commit();
    }
}