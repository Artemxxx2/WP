<?php
namespace SabaiApps\Directories\Component\CSV\Exporter;

use SabaiApps\Directories\Component\Entity\Model\Field;

class MapExporter extends AbstractExporter
{    
    protected function _csvExporterInfo()
    {
        switch ($this->_name) {
            case 'map_map':
                return array(
                    'field_types' => array($this->_name),
                    'columns' => array(
                        'lat' => __('Latitude', 'directories'),
                        'lng' => __('Longitude', 'directories'),
                        'zoom' => __('Zoom Level', 'directories'),
                    ),
                );
        }
    }
    
    public function csvExporterSettingsForm(Field $field, array $settings, $column, $enclosure, array $parents = [])
    {   
        switch ($this->_name) {
            case 'map_map':
                return $this->_acceptMultipleValues($field, $enclosure, $parents);
        }
    }
    
    public function csvExporterDoExport(Field $field, array $settings, $value, array $columns, array &$formStorage, array &$logs)
    {
        switch ($this->_name) {
            case 'map_map':
                $ret = [];
                foreach ($columns as $column) {
                    if (!isset($settings['_separator'])) {
                        $ret[$column] = $value[0][$column];
                    } else {
                        foreach ($value as $i => $_value) {
                            $ret[$column][$i] = $_value[$column];
                        }
                        $ret[$column] = implode($settings[$column]['_separator'], $ret[$column]);
                    }
                }
                return $ret;
        }
    }
}