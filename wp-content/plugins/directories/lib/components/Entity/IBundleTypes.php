<?php
namespace SabaiApps\Directories\Component\Entity;

interface IBundleTypes
{
    public function entityGetBundleTypeNames();
    public function entityGetBundleType($name);
}