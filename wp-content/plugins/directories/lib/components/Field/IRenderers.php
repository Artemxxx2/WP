<?php
namespace SabaiApps\Directories\Component\Field;

interface IRenderers
{
    public function fieldGetRendererNames();
    public function fieldGetRenderer($name);
}