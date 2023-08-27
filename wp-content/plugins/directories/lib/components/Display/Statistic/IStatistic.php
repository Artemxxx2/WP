<?php
namespace SabaiApps\Directories\Component\Display\Statistic;

use SabaiApps\Directories\Component\Entity;

interface IStatistic
{
    public function displayStatisticInfo(Entity\Model\Bundle $bundle, $key = null);
    public function displayStatisticSettingsForm(Entity\Model\Bundle $bundle, array $settings, array $parents = [], $type = 'icon');
    public function displayStatisticRender(Entity\Model\Bundle $bundle, Entity\Type\IEntity $entity, array $settings);
    public function displayStatisticIsPreRenderable(Entity\Model\Bundle $bundle, array $settings);
    public function displayStatisticPreRender(Entity\Model\Bundle $bundle, array $settings, array $entities);
}