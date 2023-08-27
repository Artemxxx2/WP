<?php
namespace SabaiApps\Directories\Component\Location\CSVImporter;

use SabaiApps\Directories\Component\CSV\Importer\AbstractImporter;
use SabaiApps\Directories\Component\CSV\Importer\IWpAllImportImporter;
use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Entity\Model\Field;
use SabaiApps\Directories\Exception;

class LocationCSVImporter extends AbstractImporter implements IWpAllImportImporter
{
    protected static $_geocodeCount = 0;
    protected $_termTitles = [];
    
    protected function _csvImporterInfo()
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
                        'term_id' => __('Location Taxonomy Term', 'directories-pro'),
                        'timezone' => __('Timezone', 'directories-pro'),
                    ),
                );
        }
    }
    
    public function csvImporterSettingsForm(Field $field, array $settings, $column, $enclosure, array $parents = [])
    {
        switch ($this->_name) {
            case 'location_address':
                $form = [];
                switch ($column) {
                    case 'address':
                        $form += array(
                            'geocode' => array(
                                '#type' => 'checkbox',
                                '#title' => __('Geocode address', 'directories-pro'),
                                '#description' => __('Use the Google geocoding service to resolve the latitude/longitude coordinate from the address. This will populate all other address components of the field.', 'directories-pro'),
                                '#default_value' => true,
                                '#horizontal' => true,
                            ),
                        );
                        break;
                    case 'term_id':
                        $form += array(
                            'type' => array(
                                '#type' => 'select',
                                '#title' => __('Taxonomy term data type', 'directories-pro'),
                                '#description' => __('Select the type of data used to specify terms.', 'directories-pro'),
                                '#options' => array(
                                    'id' => __('ID', 'directories-pro'),
                                    'slug' => __('Slug', 'directories-pro'),
                                    'title' => __('Title', 'directories-pro'),
                                ),
                                '#default_value' => 'slug',
                                '#horizontal' => true,
                            ),
                            'create' => array(
                                '#type' => 'checkbox',
                                '#title' => __('Create non-existent terms', 'directories-pro'),
                                '#default_value' => true,
                                '#horizontal' => true,
                                '#states' => array(
                                    'visible' => array(
                                        sprintf('select[name="%s[type]"]', $this->_application->Form_FieldName($parents)) => array('value' => 'title'),
                                    ),
                                ),
                            ),
                        );
                        break;
                }
        
                return $form + $this->_acceptMultipleValues($field, $enclosure, $parents);
        }
    }
    
    public function csvImporterDoImport(Field $field, array $settings, $column, $value, &$formStorage, array &$logs)
    {
        switch ($this->_name) {
            case 'location_address':
                if (!empty($settings['_multiple'])) {
                    if (!$values = explode($settings['_separator'], $value)) {
                        return;
                    }
                } else {
                    $values = array($value);
                }
                $ret = [];
        
                switch ($column) {
                    case 'address':
                        foreach ($values as $value) {
                            $value = trim($value);
                            if (!strlen($value)) continue;
                            
                            $value = [
                                'address' => $value,
                            ];
                            if ($settings['geocode']) {
                                try {
                                    $geocode_result = $this->_application->Location_Api_geocode($value['address'], false);
                                    $value += [
                                        'street' => $geocode_result['street'],
                                        'city' => $geocode_result['city'],
                                        'province' => $geocode_result['province'],
                                        'zip' => $geocode_result['zip'],
                                        'country' => $geocode_result['country'],
                                        'lat' => $geocode_result['lat'],
                                        'lng' => $geocode_result['lng'],
                                    ];
                                } catch (Exception\IException $e) {
                                    $logs['error'][] = $e->getMessage();
                                }
                                if (!empty($value['lat'])
                                    && !empty($value['lng'])
                                ) {
                                    try {
                                        $value['timezone'] = $this->_application->Location_Api_timezone([$value['lat'], $value['lng']]);
                                    } catch (Exception\IException $e) {
                                        $logs['error'][] = $e->getMessage();
                                    }
                                }
                                ++self::$_geocodeCount;
                                if (self::$_geocodeCount % 10 === 0) {
                                    sleep(1); // this is to prevent rate limit of 10 requests per second
                                }
                            }
                            $ret[] = $value;
                        }
                        break;
                    case 'term_id':
                        $ret = [];
                        switch ($settings['type']) {
                            case 'title':
                                if ((!$bundle_name = $field->Bundle->info['taxonomies']['location_location'])
                                    || (!$bundle = $this->_application->Entity_Bundle($bundle_name))
                                ) return;

                                if (!empty($this->_termTitles)) {
                                    foreach (array_keys($values) as $i) {
                                        if ($term_id = array_search(strtolower($values[$i]), $this->_termTitles)) {
                                            $ret[] = ['term_id' => $term_id];
                                            unset($values[$i]);
                                        }
                                    }
                                }
                                if (!empty($values)) {
                                    $terms = $this->_application->Entity_Types_impl($bundle->entitytype_name)->entityTypeEntitiesByTitles($bundle->name, $values);
                                    foreach ($terms as $term) {
                                        $this->_termTitles[$term->getId()] = strtolower($term->getTitle());
                                        $ret[] = ['term_id' => $term->getId()];
                                    }
                                    if ($settings['create']) {
                                        foreach ($values as $title) {
                                            if (!in_array(strtolower($title), $this->_termTitles)) {
                                                try {
                                                    $term = $this->_application->Entity_Save($bundle, array('title' => $title, 'parent' => 0));
                                                } catch (Exception\IException $e) {
                                                    $logs['error'][] = $e->getMessage();
                                                    continue;
                                                }
                                                $this->_termTitles[$term->getId()] = strtolower($term->getTitle());
                                                $ret[] = ['term_id' => $term->getId()];
                                            }
                                        }
                                    }
                                }
                                break;
                            case 'slug':
                                if ((!$bundle_name = $field->Bundle->info['taxonomies']['location_location'])
                                    || (!$bundle = $this->_application->Entity_Bundle($bundle_name))
                                ) return;

                                $terms = $this->_application->Entity_Types_impl($bundle->entitytype_name)->entityTypeEntitiesBySlugs($bundle->name, $values);
                                foreach ($terms as $term) {
                                    $ret[] = ['term_id' => $term->getId()];
                                }
                                break;
                            case 'id':
                                foreach ($values as $term_id) {
                                    $ret[] = ['term_id' => $term_id];
                                }
                                break;
                        }
                        break;
                    default:
                        foreach ($values as $value) {
                            $ret[] = array($column => $value);
                        }
                }
        
                return $ret;
        }
    }

    public function csvWpAllImportImporterAddField(\RapidAddon $addon, Entity\Model\Field $field)
    {
        switch ($this->_name) {
            case 'location_address':
                $search_by_suffix = '';
                if (!$geocoding_api_enabled = $this->_application->Location_Api('Geocoding')) {
                    $search_by_suffix = ' - ' . __('WARNING! No geocoding provider selected in Settings -> Map.', 'directories-pro');
                }
                $addon->add_title($field->getFieldLabel());
                $addon->add_field(
                    $field->getFieldName() . '-type',
                    '',
                    'radio',
                    array(
                        'address' => array(
                            __('Search by address', 'directories-pro') . $search_by_suffix,
                            $addon->add_field(
                                $field->getFieldName() . '-search-address',
                                '',
                                'text'
                            ),
                        ),
                        'latlng' => array(
                            __('Search by coordinates', 'directories-pro') . $search_by_suffix,
                            $addon->add_field(
                                $field->getFieldName() . '-search-lat',
                                __('Latitude', 'directories-pro'),
                                'text',
                                null,
                                'Example: 34.0194543'
                            ),
                            $addon->add_field(
                                $field->getFieldName() . '-search-lng',
                                __('Longitude', 'directories-pro'),
                                'text',
                                null,
                                'Example: -118.4911912'
                            ),
                        ),
                        'manual' => array(
                            __('Enter manually', 'directories-pro'),
                            $addon->add_field(
                                $field->getFieldName() . '-address',
                                __('Full Address', 'directories-pro'),
                                'text'
                            ),
                            $addon->add_field(
                                $field->getFieldName() . '-street',
                                __('Address Line 1', 'directories-pro'),
                                'text'
                            ),
                            $addon->add_field(
                                $field->getFieldName() . '-street2',
                                __('Address Line 2', 'directories-pro'),
                                'text'
                            ),
                            $addon->add_field(
                                $field->getFieldName() . '-city',
                                __('City', 'directories-pro'),
                                'text'
                            ),
                            $addon->add_field(
                                $field->getFieldName() . '-province',
                                __('State / Province / Region', 'directories-pro'),
                                'text'
                            ),
                            $addon->add_field(
                                $field->getFieldName() . '-zip',
                                __('Postal / Zip Code', 'directories-pro'),
                                'text'
                            ),
                            $addon->add_field(
                                $field->getFieldName() . '-country',
                                __('Country', 'directories-pro'),
                                'text',
                                null,
                                'Example: US'
                            ),
                            $addon->add_field(
                                $field->getFieldName() . '-lat',
                                __('Latitude', 'directories-pro'),
                                'text',
                                null,
                                'Example: 34.0194543'
                            ),
                            $addon->add_field(
                                $field->getFieldName() . '-lng',
                                __('Longitude', 'directories-pro'),
                                'text',
                                null,
                                'Example: -118.4911912'
                            ),
                            $addon->add_field(
                                $field->getFieldName() . '-timezone',
                                __('Timezone', 'directories-pro'),
                                'text',
                                null,
                                'Example: America/New_York'
                            ),
                        )
                    ),
                    '',
                    false,
                    $geocoding_api_enabled ? 'address' : 'manual'
                );
                return true;
        }
    }

    public function csvWpAllImportImporterDoImport(\RapidAddon $addon, Entity\Model\Field $field, array $data, $options, array $article)
    {
        switch ($this->_name) {
            case 'location_address':
                if (!isset($data[$field->getFieldName() . '-type'])) return;

                if (!$field->isCustomField()) {
                    $addon->_save_post_callbacks[] = [[$this, 'savePostCallback'], [$field->getFieldName()]];
                }
                switch ($type = $data[$field->getFieldName() . '-type']) {
                    case 'address':
                    case 'latlng':
                        if ($type === 'address') {
                            $addon->log('[drts] Importing by address search ...');
                            if (!isset($data[$field->getFieldName() . '-search-address'])
                                || (!$address = trim($data[$field->getFieldName() . '-search-address']))
                            ) return;

                            $addon->log('[drts] Geocoding address `' . $address . '` ...');
                            try {
                                $result = $this->_application->Location_Api_geocode($address);
                            } catch (\Exception $e) {
                                $addon->log('[drts] Error geocoding address: ' . $e->getMessage());
                                return;
                            }
                            $addon->log('[drts] Geocoding results: ' . json_encode($result));
                        } else {
                            $addon->log('[drts] Importing by lat/lng search');
                            if (!isset($data[$field->getFieldName() . '-search-lat'])
                                || !isset($data[$field->getFieldName() . '-search-lng'])
                                || (!$lat = trim($data[$field->getFieldName() . '-search-lat']))
                                || (!$lng = trim($data[$field->getFieldName() . '-search-lng']))
                            ) return;

                            $addon->log('[drts] Reverse geocoding coordinates `' . $lat . ',' . $lng . '` ...');
                            try {
                                $result = $this->_application->Location_Api_reverseGeocode([$lat, $lng]);
                            } catch (\Exception $e) {
                                $addon->log('[drts] Error reverse geocoding lat/lng: ' . $e->getMessage());
                                return;
                            }
                            $addon->log('[drts] Reverse geocoding results: ' . json_encode($result));
                        }

                        $value = [];
                        foreach (['address', 'street', 'city', 'province', 'zip', 'country', 'lat', 'lng'] as $key) {
                            $value[$key] = isset($result[$key]) ? $result[$key] : '';
                        }
                        if (!empty($value['lat'])
                            && !empty($value['lng'])
                        ) {
                            try {
                                $value['timezone'] = $this->_application->Location_Api_timezone([$value['lat'], $value['lng']]);
                            } catch (\Exception $e) {
                                $addon->log('[drts] Error fetching timezone: ' . $e->getMessage());
                                return;
                            }
                        }

                        return empty($value) ? null : [$value];

                    default:
                        $addon->log('[drts] Importing by manual address input');
                        $value = [];
                        foreach (['address', 'street', 'street2', 'city', 'province', 'zip', 'country', 'timezone'] as $key) {
                            $value[$key] = $data[$field->getFieldName() . '-' . $key];
                        }
                        foreach (['lat', 'lng'] as $key) {
                            if (empty($data[$field->getFieldName() . '-' . $key])
                                || !is_numeric($data[$field->getFieldName() . '-' . $key])
                            ) continue;

                            $value[$key] = $data[$field->getFieldName() . '-' . $key];
                        }
                        return [$value];
                }
        }
    }

    public function savePostCallback($entity, &$values, $fieldName)
    {
        if (!empty($values['location_location'])
            && ($field_value = $entity->getSingleFieldValue($fieldName))
        ) {
            $location_term_ids = array_values($values['location_location']);
            $field_value['term_id'] = $location_term_ids[0];
            $values[$fieldName] = $field_value;
        }
    }
}