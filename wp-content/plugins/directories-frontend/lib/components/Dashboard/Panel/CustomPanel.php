<?php
namespace SabaiApps\Directories\Component\Dashboard\Panel;

use SabaiApps\Directories\Application;
use SabaiApps\Framework\User\AbstractIdentity;

class CustomPanel extends AbstractPanel
{
    protected $_customName;

    public function __construct(Application $application, $name)
    {
        parent::__construct($application, $name);
        $this->_customName = substr($name, strlen('custom_'));
    }

    protected function _dashboardPanelInfo()
    {
        return $this->_application->Filter('dashboard_panel_custom_info', [], [$this->_customName]) + [
            'weight' => 99,
        ];
    }

    protected function _dashboardPanelLinks(array $settings, AbstractIdentity $identity = null)
    {
        if (isset($identity)) return; // Do not show if public dashboard

        return $this->_application->Filter('dashboard_panel_custom_links', [], [$this->_customName]);
    }

    public function dashboardPanelSettingsForm(array $settings, array $parents)
    {
        return $this->_application->Filter('dashboard_panel_custom_settings', [], [$this->_customName, $settings, $parents]);
    }

    public function dashboardPanelContent($link, array $settings, array $params, AbstractIdentity $identity = null)
    {
        if (isset($identity)) return; // Do not show if public dashboard

        return $this->_application->Filter('dashboard_panel_custom_content', '', [$this->_customName, $link, $settings]);
    }
}