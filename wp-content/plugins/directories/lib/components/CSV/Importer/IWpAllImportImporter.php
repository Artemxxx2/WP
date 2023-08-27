<?php
namespace SabaiApps\Directories\Component\CSV\Importer;

use SabaiApps\Directories\Component\Entity;

interface IWpAllImportImporter
{
    public function csvWpAllImportImporterAddField(\RapidAddon $addon, Entity\Model\Field $field);
    public function csvWpAllImportImporterDoImport(\RapidAddon $addon, Entity\Model\Field $field, array $data, $options, array $article);
}