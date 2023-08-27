<?php
namespace SabaiApps\Directories\Component\Directory;

interface ITypes
{
    public function directoryGetTypeNames();
    public function directoryGetType($name);
}