<?php
namespace SabaiApps\Directories\Component\System\Controller\Admin;

use SabaiApps\Directories\Component\Form;
use SabaiApps\Directories\Application;
use SabaiApps\Directories\Context;

class InstallComponent extends Form\Controller
{
    private $_componentName;

    protected function _doGetFormSettings(Context $context, array &$formStorage)
    {
        $this->_componentName = $context->getRequest()->asStr('component_name');
        
        // Fetch component info from the file system
        $local_components = $this->LocalComponents(true);
        if (!isset($local_components[$this->_componentName])) return false;
        
        $component_local = $local_components[$this->_componentName];

        $this->_submitButtons[] = array('#btn_label' => __('Install Component', 'directories'), '#btn_color' => 'success');

        $form = array(
            '#header' => array(
                '<div class="drts-bs-alert drts-bs-alert-info">' . sprintf(
                    $this->H(__('You are about to install the %s (version: %s) component.', 'directories')),
                    '<strong>' . $this->H($this->_componentName) . '</strong>',
                    '<strong>' . $component_local['version'] . '</strong>'
                ) . '</div>'
            ),
            '#component' => $this->_componentName,
        );
        
        return $form;
    }

    public function submitForm(Form\Form $form, Context $context)
    {
        $component = $this->System_Component_install($this->_componentName);
        $this->reloadComponents(); // Refresh components to include the installed component during the installed event
        $this->Action('system_component_installed', array($component));
        $this->getPlatform()->clearCache();
        
        // Reload all routes
        $this->getComponent('System')->reloadAllRoutes();
        
        $this->Action('system_admin_component_installed', array($this->_componentName));

        $context->setSuccess($this->Url('/settings', array('refresh' => 0)))
            ->addFlash(sprintf(__('Component %s has been installed.', 'directories'), $this->_componentName));
    }
}