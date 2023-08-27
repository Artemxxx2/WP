<?php
namespace SabaiApps\Directories\Component\Location\CSVExporter;

use SabaiApps\Directories\Component\CSV\Exporter\AbstractExporter;
use SabaiApps\Directories\Component\Entity\Model\Field;

class LocationCSVExporter extends AbstractExporter
{    
    protected function _csvExporterInfo()
    {
        switch ($this->_name) {
            case 'location_address':
                return array(
                    'field_types' => array($this->_name),
                    'columns' => array(
                        'address' => __('Full Address', 'directories-pro'),
                        'street' => __('Address Line 1', 'directories-pro'),
                        'street2' => __('Address Line 2', 'directories-pro'),
                        'city' => __('City', 'directories-pro'),
                        'province' => __('State / Province / Region', 'directories-pro'),
                        'zip' => __('Postal / Zip Code', 'directories-pro'),
                        'country' => __('Country', 'directories-pro'),
                        'lat' => __('Latitude', 'directories-pro'),
                        'lng' => __('Longitude', 'directories-pro'),
                        'zoom' => __('Zoom Level', 'directories-pro'),
                        'term_id' => __('Location ID', 'directories-pro'),
                        'timezone' => __('Timezone', 'directories-pro'),
                    ),
                );
        }
    }
    
    public function csvExporterSettingsForm(Field $field, array $settings, $column, $enclosure, array $parents = [])
    {   
        switch ($this->_name) {
            case 'location_address':
                switch ($column) {
                    case 'term_id':
                        return [
                            'type' => [
                                '#type' => 'select',
                                '#title' => __('Taxonomy term data type', 'directories-pro'),
                                '#description' => __('Select the type of data used to specify terms.', 'directories-pro'),
                                '#options' => [
                                    'id' => __('ID', 'directories-pro'),
                                    'slug' => __('Slug', 'directories-pro'),
                                    'title' => __('Title', 'directories-pro'),
                                ],
                                '#default_value' => 'slug',
                                '#horizontal' => true,
                            ],
                        ] + $this->_acceptMultipleValues($field, $enclosure, $parents);
                    default:
                        return $this->_acceptMultipleValues($field, $enclosure, $parents);
                }
        }
    }
    
    public function csvExporterDoExport(Field $field, array $settings, $value, array $columns, array &$formStorage, array &$logs)
    {
        switch ($this->_name) {
            case 'location_address':
                $ret = [];
                foreach ($columns as $column) {
                    if ($column === 'term_id') {
                        switch ($settings[$column]['type']) {
                            case 'slug':
                            case 'title':
                                if ((!$location_bundle_name = $field->Bundle->info['taxonomies']['location_location'])
                                    || (!$location_bundle = $this->_application->Entity_Bundle($location_bundle_name))
                                ) {
                                    $ret[$column] = '';
                                    continue 2;
                                }
                                if (!isset($settings[$column]['_separator'])) {
                                    if (!$term = $this->_application->Entity_Entity($location_bundle->entitytype_name, $value[0][$column], false)) {
                                        $ret[$column] = '';
                                    } else {
                                        $ret[$column] = $settings[$column]['type'] === 'title' ? $term->getTitle() : $term->getSlug();
                                    }
                                } else {
                                    $ret[$column] = [];
                                    foreach (array_keys($value) as $i) {
                                        if ($term = $this->_application->Entity_Entity($location_bundle->entitytype_name, $value[$i][$column], false)) {
                                            $ret[$column][$i] = $settings[$column]['type'] === 'title' ? $term->getTitle() : $term->getSlug();
                                        }
                                    }
                                    $ret[$column] = implode($settings[$column]['_separator'], $ret[$column]);
                                }
                                continue 2;
                            case 'id':
                            default:
                                break;
                        }
                    }
                    if (!isset($settings[$column]['_separator'])) {
                        $ret[$column] = $value[0][$column];
                    } else {
                        foreach (array_keys($value) as $i) {
                            $ret[$column][$i] = $value[$i][$column];
                        }
                        $ret[$column] = implode($settings[$column]['_separator'], $ret[$column]);
                    }
                }
                return $ret;
        }
    }
}