<?php
namespace SabaiApps\Directories\Component\CSV;

interface IImporters
{
    public function csvGetImporterNames();
    public function csvGetImporter($importerName);
}