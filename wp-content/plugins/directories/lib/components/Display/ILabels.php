<?php
namespace SabaiApps\Directories\Component\Display;

use SabaiApps\Directories\Component\Entity;

interface ILabels
{
    public function displayGetLabelNames(Entity\Model\Bundle $bundle);
    public function displayGetLabel($name);
}