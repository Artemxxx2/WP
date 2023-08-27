<?php
namespace SabaiApps\Directories\Component\FrontendSubmit\CSVExporter;

use SabaiApps\Directories\Component\CSV\Exporter\AbstractExporter;

class GuestCSVExporter extends AbstractExporter
{    
    protected function _csvExporterInfo()
    {
        return [
            'field_types' => [$this->_name],
            'columns' => [
                'name' => __('Name', 'directories-frontend'),
                'email' => __('E-mail Address', 'directories-frontend'),
                'url' => __('Website URL', 'directories-frontend'),
                'guid' => __('GUID', 'directories-frontend'),
            ],
        ];
    }
}