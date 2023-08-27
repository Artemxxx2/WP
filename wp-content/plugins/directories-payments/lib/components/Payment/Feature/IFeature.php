<?php
namespace SabaiApps\Directories\Component\Payment\Feature;

use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Payment\Model\Feature;
use SabaiApps\Directories\Component\Payment\IPlan;

interface IFeature
{
    public function paymentFeatureInfo($key = null);
    public function paymentFeatureSettings(Entity\Model\Bundle $bundle, $planType = 'base');
    public function paymentFeatureSupports(Entity\Model\Bundle $bundle, $planType = 'base');
    public function paymentFeatureSettingsForm(Entity\Model\Bundle $bundle, array $settings, $planType = 'base', array $parents = []);
    public function paymentFeatureIsEnabled(Entity\Model\Bundle $bundle, array $settings);
    public function paymentFeatureOnEntityForm(Entity\Model\Bundle $bundle, array $settings, array &$form, Entity\Type\IEntity $entity = null, $isAdmin = false);
    public function paymentFeatureOnAdded(Entity\Type\IEntity $entity, Feature $feature, array $settings, IPlan $plan, array &$values);
    public function paymentFeatureApply(Entity\Type\IEntity $entity, Feature $feature, array &$values, $isAddon = false);
    public function paymentFeatureUnapply(Entity\Type\IEntity $entity, Feature $feature, array &$values, $isAddon = false);
    public function paymentFeatureRender(Entity\Model\Bundle $bundle, array $settings, $planType = null);
}