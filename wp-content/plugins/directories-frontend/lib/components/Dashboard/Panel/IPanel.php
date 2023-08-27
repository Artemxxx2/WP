<?php
namespace SabaiApps\Directories\Component\Dashboard\Panel;

use SabaiApps\Framework\User\AbstractIdentity;

interface IPanel
{
    public function dashboardPanelInfo($key = null);
    public function dashboardPanelOnLoad($isPublic = false);
    public function dashboardPanelLabel();
    public function dashboardPanelLinks(array $settings, AbstractIdentity $identity = null);
    public function dashboardPanelContent($link, array $settings, array $params, AbstractIdentity $identity = null);
    public function dashboardPanelSettingsForm(array $settings, array $parents);
}