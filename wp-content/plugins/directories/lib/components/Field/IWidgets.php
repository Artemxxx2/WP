<?php
namespace SabaiApps\Directories\Component\Field;

interface IWidgets
{
    public function fieldGetWidgetNames();
    public function fieldGetWidget($name);
}