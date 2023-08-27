<?php
namespace SabaiApps\Directories\Component\CSV\Exporter;

use SabaiApps\Directories\Component\Entity;

interface IExporter
{
    public function csvExporterInfo($key = null);
    public function csvExporterSettingsForm(Entity\Model\Field $field, array $settings, $column, $enclosure, array $parents = []);
    public function csvExporterDoExport(Entity\Model\Field $field, array $settings, $value, array $columns, array &$formStorage, array &$logs);
    public function csvExporterSupports(Entity\Model\Bundle $bundle, Entity\Model\Field $field);
    public function csvExporterOnComplete(Entity\Model\Field $field, array $settings, array $columns, &$formStorage, array &$logs);
}