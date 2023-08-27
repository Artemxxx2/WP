<?php
namespace SabaiApps\Directories\Component\Location\SystemTool;

use SabaiApps\Directories\Component\System\Tool\AbstractTool;
use SabaiApps\Directories\Exception;

class ReverseGeocodeSystemTool extends AbstractTool
{
    protected function _systemToolInfo()
    {
        return [
            'label' => __('Reverse geocode address', 'directories-pro'),
            'description' => __('This tool will reverse geocode lat/lng coordinates and populate (or overwrite) the default location address field of each content item.', 'directories-pro'),
            'weight' => 81,
        ];
    }

    public function systemToolSettingsForm(array $parents = [])
    {
        $form = [
            'num' => [
                '#type' => 'number',
                '#title' => __('Number of records to process per request', 'directories-pro'),
                '#horizontal' => true,
                '#default_value' => 50,
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
        foreach ($this->_application->Entity_Bundles() as $bundle) {
            if (!empty($bundle->info['is_taxonomy'])
                || (!$fields = $this->_application->Entity_Field_options($bundle, ['interface' => 'Map\FieldType\ICoordinates']))
            ) continue;

            foreach (array_keys($fields) as $field_name) {
                if (empty($langs)) {
                    $ret[$bundle->name . '-' . $field_name] = $this->_count($bundle, $field_name);
                } else {
                    foreach ($langs as $lang) {
                        $ret[$bundle->name . '-' . $field_name . '-' . $lang] = $this->_count($bundle, $field_name, $lang);
                    }
                }
            }
        }
        return $ret;
    }

    protected function _count($bundle, $fieldName, $lang = null)
    {
        return $this->_application->Entity_Query($bundle->entitytype_name, $bundle->name)->count($lang);
    }

    public function systemToolRunTask($task, array $settings, $iteration, $total, array &$storage, array &$logs)
    {
        $task_parts = explode('-', $task);
        if (count($task_parts) < 2
            || (!$bundle = $this->_application->Entity_Bundle($task_parts[0]))
            || (!$field = $this->_application->Entity_Field($bundle, $task_parts[1]))
        ) return false;

        $lang = isset($task_parts[2]) ? $task_parts[2] : null;
        $paginator = $this->_application->Entity_Query($bundle->entitytype_name, $bundle->name)
            ->sortById()
            ->paginate($settings['num'], 0, $lang)
            ->setCurrentPage($iteration);
        foreach ($paginator->getElements() as $entity) {
            if (!$values = $entity->getFieldValue($field->getFieldName())) continue;
            $save = false;
            foreach (array_keys($values) as $i) {
                if (!intval($values[$i]['lat'])
                    || !intval($values[$i]['lng'])
                ) continue;

                try {
                    $result = $this->_application->Location_Api_reverseGeocode([$values[$i]['lat'], $values[$i]['lng']], false);
                    $values[$i]['address'] = $result['address'];
                    $values[$i]['street2'] = '';
                    foreach (['street', 'city', 'province', 'zip', 'country'] as $address_key) {
                        $values[$i][$address_key] = isset($result[$address_key]) ? $result[$address_key] : '';
                    }
                    $save = true;
                } catch (Exception\IException $e) {
                    $title = $entity->getTitle();
                    if (!strlen($title)) $title = __('(no title)', 'directories-pro');
                    $logs['error'][] = sprintf(
                        'Failed fetching reverse geocoding data for %s (ID: %d, Error: %s)',
                        $title,
                        $entity->getId(),
                        $e->getMessage()
                    );
                }
                if (empty($values[$i]['timezone'])) {
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
                    }
                }
            }
            if ($save) {
                try {
                    $this->_application->Entity_Save($entity, [$field->getFieldName() => $values]);
                } catch (Exception\IException $e) {
                    $logs['error'][] = $e->getMessage();
                }
            }
        }
        return $paginator->getElementLimit();
    }
}