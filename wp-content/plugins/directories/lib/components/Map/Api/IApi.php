<?php
namespace SabaiApps\Directories\Component\Map\Api;

interface IApi
{
    public function mapApiInfo();
    public function mapApiLoad(array $settings, array $mapSettings);
    public function mapApiSettingsForm(array $settings, array $parents);
    public function mapApiMapSettingsForm(array $mapSettings, array $parents);
}