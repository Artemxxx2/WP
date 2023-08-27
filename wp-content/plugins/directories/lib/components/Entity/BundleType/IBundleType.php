<?php
namespace SabaiApps\Directories\Component\Entity\BundleType;

interface IBundleType
{
    public function entityBundleTypeInfo();
    public function entityBundleTypeSettingsForm(array $settings, array $parents = []);
}