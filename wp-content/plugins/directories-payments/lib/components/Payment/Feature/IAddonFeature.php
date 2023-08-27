<?php
namespace SabaiApps\Directories\Component\Payment\Feature;

use SabaiApps\Directories\Component\Entity;

interface IAddonFeature
{
    public function paymentAddonFeatureSupports(Entity\Model\Bundle $bundle);
    public function paymentAddonFeatureSettingsForm(Entity\Model\Bundle $bundle, array $settings, array $parents = []);
    public function paymentAddonFeatureIsEnabled(Entity\Model\Bundle $bundle, array $settings);
    public function paymentAddonFeatureIsOrderable(array $settings, array $currentFeatures);
    public function paymentAddonFeatureExtraIsOrderable(array $planFeatures);
    public function paymentAddonFeatureExtraSettingsForm(Entity\Model\Bundle $bundle, array $planFeatures, array $currentExtraFeatures, array $parents = []);
}