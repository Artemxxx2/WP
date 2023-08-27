<?php
namespace SabaiApps\Directories\Component\Display;

use SabaiApps\Directories\Component\Entity;

interface IStatistics
{
    public function displayGetStatisticNames(Entity\Model\Bundle $bundle);
    public function displayGetStatistic($name);
}