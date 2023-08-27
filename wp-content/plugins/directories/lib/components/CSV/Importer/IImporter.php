<?php
namespace SabaiApps\Directories\Component\CSV\Importer;

use SabaiApps\Directories\Component\Entity;

interface IImporter
{
    public function csvImporterInfo($key = null);
    public function csvImporterSettingsForm(Entity\Model\Field $field, array $settings, $column, $enclosure, array $parents = []);
    public function csvImporterDoImport(Entity\Model\Field $field, array $settings, $column, $value, &$formStorage, array &$logs);
    public function csvImporterSupports(Entity\Model\Bundle $bundle, Entity\Model\Field $field);
    public function csvImporterOnComplete(Entity\Model\Field $field, array $settings, $column, &$formStorage, array &$logs);
}