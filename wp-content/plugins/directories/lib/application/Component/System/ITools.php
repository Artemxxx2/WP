<?php
namespace SabaiApps\Directories\Component\System;

interface ITools
{
    public function systemGetToolNames();
    public function systemGetTool($name);
}