<?php
namespace SabaiApps\Directories\Component\Location\Api;

interface IApi
{
    public function locationApiInfo($key = null);
    public function locationApiLoad(array $settings);
    public function locationApiSettingsForm(array $settings, array $parents);
}