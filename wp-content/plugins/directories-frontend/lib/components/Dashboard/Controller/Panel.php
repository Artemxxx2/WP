<?php
namespace SabaiApps\Directories\Component\Dashboard\Controller;

use SabaiApps\Directories\Controller;
use SabaiApps\Directories\Context;

class Panel extends Controller
{
    protected function _doExecute(Context $context)
    {
        $context->clearTemplates();
        if ($context->dashboard_panel
            && ($panel_name = $context->dashboard_panel)
            && ($panel = $this->Dashboard_Panels_impl($panel_name, true))
        ) {
            $dashboard_config = $this->getComponent('Dashboard')->getConfig();
            $panel_settings = isset($dashboard_config['panel_settings'][$panel_name]) ? $dashboard_config['panel_settings'][$panel_name] : [];
            $panel_settings += (array)$panel->dashboardPanelInfo('default_settings');
            $context->content = $panel->dashboardPanelContent(
                $context->dashboard_panel_link,
                $panel_settings,
                $context->getRequest()->getParams(),
                isset($context->dashboard_user) ? $context->dashboard_user : null
            );
        } else {
            $context->content = __('No dashboard panels found.', 'directories-frontend');
        }
    }
}