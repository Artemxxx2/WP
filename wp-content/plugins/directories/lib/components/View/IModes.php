<?php
namespace SabaiApps\Directories\Component\View;

interface IModes
{
    public function viewGetModeNames();
    public function viewGetMode($name);
}