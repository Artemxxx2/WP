<?php
namespace SabaiApps\Directories\Component\View\Mode;

use SabaiApps\Directories\Component\Entity\Model\Bundle;
use SabaiApps\Directories\Component\Entity\Type\Query;
use SabaiApps\Directories\Context;

interface IMode
{
    public function viewModeInfo($key = null);
    public function viewModeSupports(Bundle $bundle);
    public function viewModeSettingsForm(Bundle $bundle, array $settings, array $parents = []);
    public function viewModeNav(Bundle $bundle, array $settings);
    public function viewModeAssets(Bundle $bundle, array $settings);
    public function viewModeOnView(Bundle $bundle, Query $query, Context $context);
}