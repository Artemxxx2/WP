<?php
namespace SabaiApps\Directories\Component\System\Widget;

interface IWidget
{
    public function systemWidgetInfo();
    public function systemWidgetSettings(array $settings);
    public function systemWidgetContent(array $settings);
    public function systemWidgetOnSettingsSaved(array $settings, array $oldSettings);
}