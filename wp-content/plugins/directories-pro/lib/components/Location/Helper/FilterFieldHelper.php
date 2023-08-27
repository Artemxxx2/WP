<?php
namespace SabaiApps\Directories\Component\Location\Helper;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Exception;
use SabaiApps\Directories\Component\Field;

class FilterFieldHelper
{
    public function help(Application $application, Field\IField $field, Field\Query $query, array $value, array $options, array &$sorts)
    {        
        if (empty($value['_pre_filtered'])
            && (false === $value = $this->preFilter($application, $value, isset($options['default_radius']) ? $options['default_radius'] : 0))
        ) return;
 
        if (empty($value['term_id'])) {
            $field_name = $field->getFieldName();
            if (empty($value['viewport'])) {
                list($lat, $lng) = explode(',', $value['center']);
                $query->addCriteria($application->Map_IsNearbyCriteria($field, $lat, $lng, $value['radius']));
                // Add args for sort by distance
                if (isset($sorts[$field_name])) {
                    $sorts[$field_name]['args'] = array('asc', $lat, $lng);
                }
            } else {
                $v = $value['viewport'];
                $query->fieldIsOrGreaterThan($field_name, $v[0], 'lat')
                    ->fieldIsOrSmallerThan($field_name, $v[2], 'lat')
                    ->fieldIsOrGreaterThan($field_name, $v[1], 'lng')
                    ->fieldIsOrSmallerThan($field_name, $v[3], 'lng');
                // Add args for sort by distance
                if (isset($sorts[$field_name])) {
                    $sorts[$field_name]['args'] = array('asc', ($v[0] + $v[2]) / 2, ($v[1] + $v[3]) / 2);
                }
            }
            // Add and group concat extra field to fetch multiple entries for single entity but count as 1
            $query->addExtraField('filtered', $field_name, 1, true, true);
        } else {
            if (!empty($value['taxonomy'])
                && ($location_bundle = $application->Entity_Bundle($value['taxonomy']))
            ) {
                $query->taxonomyTermIdIs($location_bundle->type, $value['term_id'], false);
            }
        }
    }
    
    public function preFilter(Application $application, $value, $defaultRadius)
    {
        // Allow request value sent as string instead of array
        if (is_string($value)) {
            $value = array('text' => $value);
        }
        
        $value['_pre_filtered'] = true;
        if (empty($value['term_id'])
            || empty($value['taxonomy'])
        ) {
            unset($value['term_id'], $value['taxonomy']);
            if (!isset($value['radius'])
                || !strlen($value['radius'] = trim($value['radius']))
            ) {
                $value['radius'] = $defaultRadius;
            }
            if (!empty($value['viewport']) && !empty($value['zoom'])) {
                // Current view search, ignore radius
                $value['radius'] = null;
            }
            if (empty($value['radius'])) {
                // Fetch viewport if no distance specified
                if (empty($value['viewport'])) {
                    if (!isset($value['text']) || !strlen($value['text'])) return false;
                        
                    try {
                        $geo = $application->Location_Api_geocode($value['text']);
                    } catch (Exception\IException $e) {
                        $application->logError($e);
                        return false;
                    }
                    if (!$value['viewport'] = $geo['viewport']) {
                        // No viewport returned, set center and radius
                        $value['center'] = $geo['lat'] . ',' . $geo['lng'];
                        $value['radius'] = 10;
                    }
                } else {
                    if (is_string($value['viewport'])) {
                        if ((!$swne = explode(',', $value['viewport']))
                            || count($swne) !== 4
                        ) return false;
                        
                        $value['viewport'] = array($swne[0], $swne[1], $swne[2], $swne[3]);
                    }
                    if (!$value['viewport'] = array_filter($value['viewport'])) return false;
                }
            } else {
                $value['viewport'] = null;
                if (empty($value['center'])) {
                    if (!isset($value['text']) || !strlen($value['text'])) return false;
                        
                    try {
                        $geo = $application->Location_Api_geocode($value['text']);
                    } catch (Exception\IException $e) {
                        $application->logError($e);
                        return false;
                    }
                    $value['center'] = $geo['lat'] . ',' . $geo['lng'];
                }
            }
        } else {
            if (!$taxonomy_bundle = $application->Entity_Bundle($value['taxonomy'])) return false;
        }
        
        return $value;
    }
}