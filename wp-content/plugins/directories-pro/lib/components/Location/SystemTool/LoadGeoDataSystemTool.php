<?php
namespace SabaiApps\Directories\Component\Location\SystemTool;

use SabaiApps\Directories\Component\System\Tool\AbstractTool;
use SabaiApps\Directories\Exception;

class LoadGeoDataSystemTool extends AbstractTool
{
    protected function _systemToolInfo()
    {
        return [
            'label' => __('Load geolocation data', 'directories-pro'),
            'description' => __('This tool will load geolocation data (lat/lng/timezone) for the default location address field of each content item.', 'directories-pro'),
            'weight' => 80,
        ];
    }

    public function systemToolSettingsForm(array $parents = [])
    {
        $fields = [];
        foreach ($this->_application->Entity_Bundles() as $bundle) {
            if (!empty($bundle->info['is_taxonomy'])
                || (!$_fields = $this->_application->Entity_Field_options($bundle, ['interface' => 'Map\FieldType\ICoordinates', 'prefix' => $bundle->getGroupLabel() . ' - ']))
            ) continue;

            foreach (array_keys($_fields) as $field_name) {
                $fields[$bundle->name . '-' . $field_name] = $_fields[$field_name];
            }
        }
        $form = [
            'fields' => [
                '#type' => 'checkboxes',
                '#title' => __('Select fields', 'directories-pro'),
                '#options' => $fields,
                '#horizontal' => true,
                '#default_value' => array_keys($fields),
                '#required' => true,
            ],
            'overwrite' => [
                '#type' => 'checkbox',
                '#title' => __('Overwrite current data (if any)', 'directories-pro'),
                '#horizontal' => true,
                '#default_value' => false,
            ],
            'num' => [
                '#type' => 'number',
                '#title' => __('Number of records to process per request', 'directories-pro'),
                '#horizontal' => true,
                '#default_value' => 10,
                '#min_value' => 1,
                '#integer' => true,
                '#required' => true,
            ],
        ];

        return $form;
    }

    public function systemToolInit(array $settings, array &$storage, array &$logs)
    {
        $ret = [];
        $langs = $this->_application->getPlatform()->getLanguages();
        foreach ($settings['fields'] as $field) {
            list($bundle_name, $field_name) = explode('-', $field);
            if (!$bundle = $this->_application->Entity_Bundle($bundle_name)) continue;

            if (empty($langs)) {
                $ret[$bundle_name . '-' . $field_name] = $this->_getQuery($bundle, $field_name, $settings)->count();
            } else {
                foreach ($langs as $lang) {
                    $ret[$bundle_name . '-' . $field_name . '-' . $lang] = $this->_getQuery($bundle, $field_name, $settings)->count($lang);
                }
            }
        }
        return $ret;
    }

    protected function _getQuery($bundle, $fieldName, array $settings)
    {
        $query = $this->_application->Entity_Query($bundle->entitytype_name, $bundle->name);
        if (empty($settings['overwrite'])) {
            $query->startCriteriaGroup('OR')
                ->startCriteriaGroup()
                ->fieldIs($fieldName, 0, 'lat')
                ->fieldIs($fieldName, 0, 'lng')
                ->finishCriteriaGroup()
                ->fieldIs($fieldName, '', 'timezone')
                ->finishCriteriaGroup();
        }
        return $query;
    }

    public function systemToolRunTask($task, array $settings, $iteration, $total, array &$storage, array &$logs)
    {
        list($bundle_name, $field_name) = explode('-', $task);
        if ((!$bundle = $this->_application->Entity_Bundle($bundle_name))
            || !$this->_application->Entity_Field($bundle, $field_name)
        ) return false;

        $lang = isset($task_parts[2]) ? $task_parts[2] : null;
        $paginator = $this->_getQuery($bundle, $field_name, $settings)
            ->sortById()
            ->paginate($settings['num'], 0, $lang)
            ->setCurrentPage($iteration);
        foreach ($paginator->getElements() as $entity) {
            $values = $entity->getFieldValue($field_name);
            $save = false;
            $terms = [];
            if ($field_name === 'location_address') {
                if ($_terms = $entity->getFieldValue('location_location')) {
                    foreach ($_terms as $term) {
                        $terms[$term->getId()] = $term;
                    }
                }
            }
            foreach (array_keys($values) as $i) {
                if (!empty($settings['overwrite'])
                    || (empty($values[$i]['lat']) && empty($values[$i]['lng']))
                ) {
                    $query = []; // string passed to geocoding API

                    if (!empty($values[$i]['address'])) {
                        // Use full address
                        $query[] = $values[$i]['address'];
                    } else {
                        foreach (['street', 'street2', 'city', 'province', 'zip', 'country'] as $input_key) {
                            if (isset($values[$i][$input_key])) {
                                $query[] = $values[$i][$input_key];
                            }
                        }
                        // Append location term titles
                        if ($field_name === 'location_address'
                            && !empty($values[$i]['term_id'])
                            && isset($terms[$values[$i]['term_id']])
                        ) {
                            $term = $terms[$values[$i]['term_id']];
                            $query[] = $term->getTitle();
                            if ($parent_term_titles = $term->getCustomProperty('parent_titles')) {
                                foreach (array_reverse($parent_term_titles) as $term_title) {
                                    $query[] = $term_title;
                                }
                            }
                        }
                    }

                    $query = trim(implode(' ', $query));
                    if (!strlen($query)) {
                        $logs['warning'][] = sprintf(
                            'Skipped fetching geolocation data for %s (ID: %d, Error: No address data to geocode)',
                            $entity->getTitle(),
                            $entity->getId()
                        );
                        continue;
                    } // skip since nothing to query

                    try {
                        $result = $this->_application->Location_Api_geocode($query, false);
                        $values[$i]['lat'] = $result['lat'];
                        $values[$i]['lng'] = $result['lng'];
                        $save = true;
                    } catch (Exception\IException $e) {
                        $title = $entity->getTitle();
                        if (!strlen($title)) $title = __('(no title)', 'directories-pro');
                        $logs['error'][] = sprintf(
                            'Failed fetching geolocation data for %s (ID: %d, Error: %s)',
                            $title,
                            $entity->getId(),
                            $e->getMessage()
                        );
                        continue;
                    }
                }
                if (!empty($settings['overwrite'])
                    || empty($values[$i]['timezone'])
                ) {
                    try {
                        $values[$i]['timezone'] = $this->_application->Location_Api_timezone([$values[$i]['lat'], $values[$i]['lng']]);
                        $save = true;
                    } catch (Exception\IException $e) {
                        $title = $entity->getTitle();
                        if (!strlen($title)) $title = __('(no title)', 'directories-pro');
                        $logs['error'][] = sprintf(
                            'Failed fetching timezone for %s (ID: %d, Error: %s)',
                            $title,
                            $entity->getId(),
                            $e->getMessage()
                        );
                        continue;
                    }
                }
            }
            if ($save) {
                try {
                    $this->_application->Entity_Save($entity, [$field_name => $values]);
                } catch (Exception\IException $e) {
                    $logs['error'][] = $e->getMessage();
                }
            }
        }
        return $paginator->getElementLimit();
    }
}