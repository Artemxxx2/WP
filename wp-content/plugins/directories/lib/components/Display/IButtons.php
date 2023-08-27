<?php
namespace SabaiApps\Directories\Component\Display;

use SabaiApps\Directories\Component\Entity;

interface IButtons
{
    public function displayGetButtonNames(Entity\Model\Bundle $bundle);
    public function displayGetButton($name);
}