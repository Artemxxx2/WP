<?php
namespace SabaiApps\Directories\Component\Entity;

interface ITypes
{
    public function entityGetTypeNames();
    public function entityGetType($name);
}