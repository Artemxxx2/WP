<?php
namespace SabaiApps\Directories\Component\Display\Button;

use SabaiApps\Directories\Component\Entity;

interface IButton
{
    public function displayButtonInfo(Entity\Model\Bundle $bundle, $key = null);
    public function displayButtonSettingsForm(Entity\Model\Bundle $bundle, array $settings, array $parents = []);
    public function displayButtonLink(Entity\Model\Bundle $bundle, Entity\Type\IEntity $entity, array $settings, $displayName);
    public function displayButtonIsPreRenderable(Entity\Model\Bundle $bundle, array $settings);
    public function displayButtonPreRender(Entity\Model\Bundle $bundle, array $settings, array $entities);
}