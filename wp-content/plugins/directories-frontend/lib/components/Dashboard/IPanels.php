<?php
namespace SabaiApps\Directories\Component\Dashboard;

interface IPanels
{
    public function dashboardGetPanelNames();
    public function dashboardGetPanel($name);
}