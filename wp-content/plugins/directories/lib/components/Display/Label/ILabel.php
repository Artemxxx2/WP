<?php
namespace SabaiApps\Directories\Component\Display\Label;

use SabaiApps\Directories\Component\Entity;

interface ILabel
{
    public function displayLabelInfo(Entity\Model\Bundle $bundle, $key = null);
    public function displayLabelSettingsForm(Entity\Model\Bundle $bundle, array $settings, array $parents = []);
    public function displayLabelText(Entity\Model\Bundle $bundle, Entity\Type\IEntity $entity, array $settings);
}