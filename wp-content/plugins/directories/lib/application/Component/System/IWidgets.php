<?php
namespace SabaiApps\Directories\Component\System;

interface IWidgets
{
    public function systemGetWidgetNames();
    public function systemGetWidget($widgetName);
}