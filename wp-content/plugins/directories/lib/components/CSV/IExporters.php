<?php
namespace SabaiApps\Directories\Component\CSV;

interface IExporters
{
    public function csvGetExporterNames();
    public function csvGetExporter($exporterName);
}