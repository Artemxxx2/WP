<?php
namespace SabaiApps\Directories\Component\Field;

interface ITypes
{
    public function fieldGetTypeNames();
    public function fieldGetType($name);
}