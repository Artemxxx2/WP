<?php
namespace SabaiApps\Directories\Component\FrontendSubmit\CSVImporter;

use SabaiApps\Directories\Component\CSV\Importer\AbstractImporter;

class GuestCSVImporter extends AbstractImporter
{   
    protected function _csvImporterInfo()
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