<?php
namespace SabaiApps\Directories\Component\Display;

use SabaiApps\Directories\Component\Entity;

interface IElements
{
    public function displayGetElementNames(Entity\Model\Bundle $bundle);
    public function displayGetElement($name);
}