<?php
namespace SabaiApps\Directories\Component\CSV\Helper;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Exception;

class ImportersHelper
{
    /**
     * Returns all available importers
     * @param Application $application
     */
    public function help(Application $application, $byFieldType = false, $useCache = true)
    {
        $cache_id = $byFieldType ? 'csv_importers_by_field_type' : 'csv_importers';
        if (!$useCache
            || (!$ret = $application->getPlatform()->getCache($cache_id))
        ) {
            $csv_config = $application->getComponent('CSV')->getConfig();
            $importers = $importers_by_field_type = [];
            foreach ($application->InstalledComponentsByInterface('CSV\IImporters') as $component_name) {
                if (!$application->isComponentLoaded($component_name)) continue;
                
                foreach ($application->getComponent($component_name)->csvGetImporterNames() as $importer_name) {
                    if (!$importer = $application->getComponent($component_name)->csvGetImporter($importer_name)) {
                        continue;
                    }
                    $importers[$importer_name] = $component_name;
                    
                    foreach ((array)$importer->csvImporterInfo('field_types') as $field_type) {
                        if (!isset($importers_by_field_type[$field_type])) {
                            $importers_by_field_type[$field_type] = $importer_name;
                        } else {
                            // More than one importer for the field type
                            if (isset($csv_config['default_importers'][$field_type])
                                && $csv_config['default_importers'][$field_type] === $importer_name
                            ) {
                                $importers_by_field_type[$field_type] = $importer_name;
                            }
                        }
                    }
                }
            }
            $application->getPlatform()->setCache($importers, 'csv_importers')
                ->setCache($importers_by_field_type, 'csv_importers_by_field_type');
            
            $ret = $byFieldType ? $importers_by_field_type : $importers;
        }

        return $ret;
    }
    
    private $_impls = [];

    /**
     * Gets an implementation of CSV\IImporter interface for a given importer type
     * @param Application $application
     * @param string $importer
     */
    public function impl(Application $application, $importer, $returnFalse = false)
    {
        if (!isset($this->_impls[$importer])) {
            $importers = $application->CSV_Importers();
            // Valid importer type?
            if (!isset($importers[$importer])
                || (!$application->isComponentLoaded($importers[$importer]))
            ) {                
                if ($returnFalse) return false;
                throw new Exception\UnexpectedValueException(sprintf('Invalid importer type: %s', $importer));
            }
            $this->_impls[$importer] = $application->getComponent($importers[$importer])->csvGetImporter($importer);
        }

        return $this->_impls[$importer];
    }
}