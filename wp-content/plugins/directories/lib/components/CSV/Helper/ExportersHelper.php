<?php
namespace SabaiApps\Directories\Component\CSV\Helper;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Exception;

class ExportersHelper
{
    /**
     * Returns all available exporters
     * @param Application $application
     */
    public function help(Application $application, $byFieldType = false, $useCache = true)
    {
        $cache_id = $byFieldType ? 'csv_exporters_by_field_type' : 'csv_exporters';
        if (!$useCache
            || (!$ret = $application->getPlatform()->getCache($cache_id))
        ) {
            $csv_config = $application->getComponent('CSV')->getConfig();
            $exporters = $exporters_by_field_type = [];
            foreach ($application->InstalledComponentsByInterface('CSV\IExporters') as $component_name) {
                if (!$application->isComponentLoaded($component_name)) continue;
                
                foreach ($application->getComponent($component_name)->csvGetExporterNames() as $exporter_name) {
                    if (!$exporter = $application->getComponent($component_name)->csvGetExporter($exporter_name)) {
                        continue;
                    }
                    $exporters[$exporter_name] = $component_name;
                    
                    foreach ((array)$exporter->csvExporterInfo('field_types') as $field_type) {
                        if (!isset($exporters_by_field_type[$field_type])) {
                            $exporters_by_field_type[$field_type] = $exporter_name;
                        } else {
                            // More than one exporter for the field type
                            if (isset($csv_config['default_exporters'][$field_type])
                                && $csv_config['default_exporters'][$field_type] === $exporter_name
                            ) {
                                $exporters_by_field_type[$field_type] = $exporter_name;
                            }
                        }
                    }
                }
            }
            $application->getPlatform()->setCache($exporters, 'csv_exporters')
                ->setCache($exporters_by_field_type, 'csv_exporters_by_field_type');
            
            $ret = $byFieldType ? $exporters_by_field_type : $exporters;
        }

        return $ret;
    }
    
    private $_impls = [];

    /**
     * Gets an implementation of CSV\IExporter interface for a given exporter type
     * @param Application $application
     * @param string $exporter
     */
    public function impl(Application $application, $exporter, $returnFalse = false)
    {
        if (!isset($this->_impls[$exporter])) {
            $exporters = $application->CSV_Exporters();
            // Valid exporter type?
            if (!isset($exporters[$exporter])
                || (!$application->isComponentLoaded($exporters[$exporter]))
            ) {                
                if ($returnFalse) return false;
                throw new Exception\UnexpectedValueException(sprintf('Invalid exporter type: %s', $exporter));
            }
            $this->_impls[$exporter] = $application->getComponent($exporters[$exporter])->csvGetExporter($exporter);
        }

        return $this->_impls[$exporter];
    }
}