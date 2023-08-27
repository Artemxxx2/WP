<?php
namespace SabaiApps\Directories\Component\Location\FieldType;

use SabaiApps\Directories\Component\Field;
use SabaiApps\Directories\Component\Entity\Type\IEntity;
use SabaiApps\Directories\Component\Entity\Model\Bundle;
use SabaiApps\Directories\Exception;
use SabaiApps\Directories\Application;
use SabaiApps\Directories\Component\Map;

class AddressFieldType extends Field\Type\AbstractType implements
    Field\Type\ISortable,
    Field\Type\ISchemable,
    Field\Type\IQueryable,
    Field\Type\IOpenGraph,
    Field\Type\IHumanReadable,
    Field\Type\IConditionable,
    Map\FieldType\ICoordinates,
    Field\Type\ICopiable
{
    protected function _fieldTypeInfo()
    {
        return [
            'label' => __('Location', 'directories-pro'),
            'default_settings' => [
                'format' => '{street} {street2}, {city}, {province} {zip}, {country}',
            ],
            'icon' => 'fas fa-map-marked-alt',
            'entity_cache_clear' => true,
            'conditionable' => true,
            'on_load_priority' => 10, // make sure to be loaded after location_location field for FormatAddress helper to work properly
        ];
    }

    public function fieldTypeSchema()
    {
        return [
            'columns' => [
                'address' => [
                    'type' => Application::COLUMN_VARCHAR,
                    'length' => 255,
                    'notnull' => true,
                    'was' => 'address',
                    'default' => '',
                ],
                'street' => [
                    'type' => Application::COLUMN_VARCHAR,
                    'length' => 255,
                    'notnull' => true,
                    'was' => 'street',
                    'default' => '',
                ],
                'street2' => [
                    'type' => Application::COLUMN_VARCHAR,
                    'length' => 255,
                    'notnull' => true,
                    'was' => 'street2',
                    'default' => '',
                ],
                'city' => [
                    'type' => Application::COLUMN_VARCHAR,
                    'length' => 100,
                    'notnull' => true,
                    'was' => 'city',
                    'default' => '',
                ],
                'province' => [
                    'type' => Application::COLUMN_VARCHAR,
                    'length' => 100,
                    'notnull' => true,
                    'was' => 'state',
                    'default' => '',
                ],
                'zip' => [
                    'type' => Application::COLUMN_VARCHAR,
                    'length' => 30,
                    'notnull' => true,
                    'was' => 'zip',
                    'default' => '',
                ],
                'country' => [
                    'type' => Application::COLUMN_VARCHAR,
                    'length' => 50,
                    'notnull' => true,
                    'was' => 'country',
                    'default' => '',
                ],
                'timezone' => [
                    'type' => Application::COLUMN_VARCHAR,
                    'length' => 50,
                    'notnull' => true,
                    'was' => 'timezone',
                    'default' => '',
                ],
                'zoom' => [
                    'type' => Application::COLUMN_INTEGER,
                    'unsigned' => true,
                    'notnull' => true,
                    'length' => 2,
                    'was' => 'zoom',
                    'default' => 10,
                ],
                'lat' => [
                    'type' => Application::COLUMN_DECIMAL,
                    'length' => 9,
                    'scale' => 6,
                    'notnull' => true,
                    'unsigned' => false,
                    'was' => 'lat',
                    'default' => 0,
                ],
                'lng' => [
                    'type' => Application::COLUMN_DECIMAL,
                    'length' => 9,
                    'scale' => 6,
                    'notnull' => true,
                    'unsigned' => false,
                    'was' => 'lng',
                    'default' => 0,
                ],
                'term_id' => [
                    'type' => Application::COLUMN_INTEGER,
                    'unsigned' => true,
                    'notnull' => true,
                    'was' => 'term_id',
                    'default' => 0,
                ],
            ],
            'indexes' => [
                'lat_lng' => [
                    'fields' => [
                        'lat' => ['sorting' => 'ascending'],
                        'lng' => ['sorting' => 'ascending'],
                    ],
                    'was' => 'lat_lng',
                ],
            ],
        ];
    }

    public function fieldTypeSettingsForm($fieldType, Bundle $bundle, array $settings, array $parents = [], array $rootParents = [])
    {
        if (is_object($fieldType)
            && !$fieldType->isCustomField()
        ) {
            $location_bundle = $this->_getLocationBundle($bundle);
            $location_hierarchy = $this->_application->Location_Hierarchy($location_bundle ? $location_bundle : null);
        } else {
            $location_hierarchy = null;
        }

        return [
            'format' => [
                '#type' => 'textfield',
                '#title' => __('Address format', 'directories-pro'),
                '#description' => $this->_application->System_Util_availableTags($this->_application->Location_FormatAddress_tags($bundle, $location_hierarchy)),
                '#description_no_escape' => true,
                '#default_value' => $settings['format'],
            ],
        ];
    }

    public function fieldTypeOnSave(Field\IField $field, array $values, array $currentValues = null, array &$extraArgs = [])
    {
        $ret = [];
        foreach ($values as $weight => $value) {
            if (is_array($value)) {
                unset($value['_latlng']); // this may be sent from form
                foreach (['city', 'zip', 'country', 'province'] as $key) {
                    if (isset($value[$key])
                        && is_array($value[$key])
                    ) {
                        $value[$key] = trim((string)array_shift($value[$key]));
                    }
                }
                if (isset($value['lat'])
                    && ($lat = trim($value['lat']))
                    && ($lat = (float)$lat)
                ) {
                    $value['lat'] = $lat;
                    if (isset($value['lng'])
                        && ($lng = trim($value['lng']))
                        && ($lng = (float)$lng)
                    ) {
                        $value['lng'] = $lng;
                        if (isset($value['zoom'])) {
                            $value['zoom'] = (int)$value['zoom'];
                        }
                    } else {
                        unset($value['lat'], $value['lng'], $value['zoom'], $value['timezone']);
                    }
                } else {
                    unset($value['lat'], $value['lng'], $value['zoom'], $value['timezone']);
                }
            } else {
                if (!is_numeric($value)) continue;

                $value = ['term_id' => $value];
            }
            if (isset($value['term_id'])) {
                $value['term_id'] = (int)$value['term_id'];
            }

            if ($value = array_filter($value)) {
                $ret[] = $value;
            }
        }

        return $ret;
    }

    public function fieldTypeIsModified(Field\IField $field, $valueToSave, $currentLoadedValue)
    {
        foreach (array_keys($currentLoadedValue) as $key) {
            $currentLoadedValue[$key] = array_filter($currentLoadedValue[$key]);
            unset($currentLoadedValue[$key]['display_address']);
        }
        if (count($currentLoadedValue) !== count($valueToSave)) return true;

        foreach (array_keys($currentLoadedValue) as $key) {
            if (count($currentLoadedValue[$key]) !== count($valueToSave[$key])
                || array_diff_assoc($currentLoadedValue[$key], $valueToSave[$key])
            ) return true;
        }
        return false;
    }

    public function fieldTypeOnLoad(Field\IField $field, array &$values, IEntity $entity, array $allValues)
    {
        $settings = $field->getFieldSettings();
        $format = isset($settings['format']) ? $settings['format'] : $this->_fieldTypeInfo()['default_settings']['format'];
        if ($field->Bundle
            && ($location_bundle = $this->_getLocationBundle($field->Bundle))
        ) {
            $location_hierarchy = $this->_application->Location_Hierarchy($location_bundle);
        } else {
            $location_hierarchy = null;
        }
        foreach (array_keys($values) as $key) {
            settype($values[$key]['lat'], 'float');
            settype($values[$key]['lng'], 'float');
            $location_terms = empty($allValues['location_location']) ? [] : $allValues['location_location'];
            $values[$key]['display_address'] = $this->_application->Location_FormatAddress($values[$key], $format, $location_hierarchy, $entity, $location_terms);
            if (!strlen($values[$key]['display_address'])) {
                $values[$key]['display_address'] = $values[$key]['address'];
            }
        }
    }

    public function fieldSortableOptions(Field\IField $field)
    {
        return [
            ['label' => $field->getFieldName() === 'location_address' ? __('Distance', 'directories-pro') : __('Distance from %s', 'directories-pro')],
        ];
    }
    
    public function fieldSortableSort(Field\Query $query, $fieldName, array $args = null)
    {
        if (isset($args[1])) {
            if (is_array($args[1]) && !empty($args[1])) {
                // Args passed from query settings of view
                switch (count($args[1])) {
                    case 1:
                    case 2:
                        if ($args[1][0] === '_current_') {
                            if (($entity = $this->_getCurrentEntity())
                                && ($location = $entity->getSingleFieldValue($fieldName))
                                && !empty($location['lat'])
                                && !empty($location['lng'])
                            ) {
                                $lat = $location['lat'];
                                $lng = $location['lng'];
                            } else {
                                $this->_application->logError('Failed fetching current entity lat/lng for sorting by distance.');
                            }
                        } elseif ($args[1][0] === '_current_user_') {
                            if ($this->_application->Location_IsSearchRequested()) return;

                            try {
                                $geo = $this->_application->Location_Api_geolocateIp();
                                $lat = $geo['lat'];
                                $lng = $geo['lng'];
                            } catch (Exception\IException $e) {
                                $this->_application->logError('Failed fetching lat/lng of current user for sorting by distance. Geolocation error: ' . $e);
                            }
                        } else {
                            try {
                                if (!$geo = $this->_application->Location_Api_geocode($args[1][0], false)) return;

                                $lat = $geo['lat'];
                                $lng = $geo['lng'];
                            } catch (Exception\IException $e) {
                                $this->_application->logError('Failed fetching lat/lng of ' . $args[1][0] . ' for sorting by distance. Geocode error: ' . $e);
                            }
                        }
                        break;
                    default:
                        // Args passed from Location_FilterField helper
                        $lat = $args[1][0];
                        $lng = $args[1][1];
                }
            } else {
                if (isset($args[2])) {
                    $lat = $args[1];
                    $lng = $args[2];
                }
            }
        }
        $config = $this->_application->getComponent('Map')->getConfig('map');
        if (!isset($lat)
            || !isset($lng)
        ) {
            $lat = $config['default_location']['lat'];
            $lng = $config['default_location']['lng'];
        }
        if (empty($lat) || empty($lng)) return;

        $sql = sprintf(
            '(%1$d * acos(cos(radians(%3$.6F)) * cos(radians(%2$s.lat)) * cos(radians(%2$s.lng) - radians(%4$.6F)) + sin(radians(%3$.6F)) * sin(radians(%2$s.lat))))',
            $config['distance_unit'] === 'mi' ? 3959 : 6371,
            $fieldName,
            $lat,
            $lng
        );
        $query->sortByField($fieldName, 'EMPTY_LAST', 'lat') // moves NULL or 0 to last in order
            ->removeExtraField('filtered')
            ->addExtraField('distance', $fieldName, $sql, true, true)
            ->sortByExtraField('distance', isset($args[0]) && $args[0] === 'desc' ? 'DESC' : 'ASC');
    }
    
    public function fieldSchemaProperties()
    {
        return ['address', 'geo', 'location', 'jobLocation'];
    }
    
    public function fieldSchemaRenderProperty(Field\IField $field, $property, IEntity $entity)
    {
        if (!$value = $entity->getFieldValue($field->getFieldName())) return;
     
        $ret = [];
        switch ($property) {
            case 'address':
            case 'location':
            case 'jobLocation':
                if ($field->Bundle
                    && ($location_bundle = $this->_getLocationBundle($field->Bundle))
                ) {
                    $location_hierarchy = $this->_application->Location_Hierarchy($location_bundle);
                    if (!isset($location_hierarchy['country'])
                        && !isset($location_hierarchy['province'])
                        && !isset($location_hierarchy['city'])
                    ) {
                        unset($location_hierarchy);
                    }
                }
                foreach ($value as $_value) {
                    if ($property === 'address') {
                        $_ret = [
                            '@type' => 'PostalAddress',
                            'addressCountry' => $_value['country'],
                            'addressRegion' => $_value['province'],
                            'addressLocality' => $_value['city'],
                            'postalCode' => $_value['zip'],
                            'streetAddress' => $_value['street'],
                        ];
                    } else {
                        $_ret = [
                            '@type' => 'Place',
                            'name' => $entity->getTitle(), // @todo should allow manual input of this value
                            'address' => [
                                '@type' => 'PostalAddress',
                                'addressCountry' => $_value['country'],
                                'addressRegion' => $_value['province'],
                                'addressLocality' => $_value['city'],
                                'postalCode' => $_value['zip'],
                                'streetAddress' => $_value['street'],
                            ],
                        ];
                    }
                    if (isset($location_hierarchy)
                        && !empty($_value['term_id'])
                        && ($term = $entity->getSingleFieldValue($location_bundle->type))
                    ) {
                        $location_titles = (array)$term->getCustomProperty('parent_titles');
                        $location_titles[$term->getId()] = $term->getTitle();
                        foreach (array_keys($location_hierarchy) as $key) {
                            if (!$prop = (string)array_shift($location_titles)) break;

                            switch ($key) {
                                case 'country':
                                    $prop_name = 'addressCountry';
                                    break;
                                case 'province':
                                    $prop_name = 'addressRegion';
                                    break;
                                case 'city':
                                    $prop_name = 'addressLocality';
                                    break;
                                default:
                                    continue 2;
                            }
                            if ($property === 'address') {
                                $_ret[$prop_name] = $prop;
                            } else {
                                $_ret['address'][$prop_name] = $prop;
                            }
                        }
                    }
                    $ret[] = $_ret;
                }
                break;
            case 'geo':
                foreach ($value as $_value) {
                    $ret[] = [
                        '@type' => 'GeoCoordinates',
                        'latitude' => $_value['lat'],
                        'longitude' => $_value['lng'],
                    ];
                }
                break;
        }
        return $ret;
    }
    
    public function fieldQueryableInfo(Field\IField $field, $inAdmin = false)
    {
        $tip = __('Enter an address (no commas) to query by address. Enter two values (address, radius) separated with a comma to specify a search radius. Enter three values (latitude, longitude, radius) separated with commas to query by coordinates.', 'directories-pro');
        if (!$inAdmin) {
            $tip .= ' ' . __('Enter "_current_" for the address of the current post if any.', 'directories-pro');
        }
        return [
            'example' => 'New York USA,10',
            'tip' => $tip,
        ];
    }
    
    public function fieldQueryableQuery(Field\Query $query, $fieldName, $paramStr, Bundle $bundle)
    {
        if (!$field = $this->_application->Entity_Field($bundle, $fieldName)) return;
        
        if (!$params = $this->_queryableParams($paramStr)) return;
                
        switch (count($params)) {
            case 1:
                // Check if field exists if "1" passed, used by Map view mode
                if ($params[0] == 1)  {
                    $query->fieldIsNotNull($fieldName, 'lat')
                        ->fieldIsNot($fieldName, 0, 'lat')
                        ->fieldIsNot($fieldName, 0, 'lng');
                    return;
                }

                if ($params[0] === '_current_') {
                    if ((!$entity = $this->_getCurrentEntity())
                        || (!$location = $entity->getSingleFieldValue($fieldName))
                        || empty($location['address'])
                    ) return;
                        
                    $params[0] = $location['address'];
                } elseif ($params[0] === '_current_user_') {
                    // No radius, for sort by distance only
                    return;
                }
                $geo = $this->_application->Location_Api_geocode($params[0], false);
                $query->fieldIsOrGreaterThan($fieldName, $geo['viewport'][0], 'lat')
                    ->fieldIsOrSmallerThan($fieldName, $geo['viewport'][2], 'lat')
                    ->fieldIsOrGreaterThan($fieldName, $geo['viewport'][1], 'lng')
                    ->fieldIsOrSmallerThan($fieldName, $geo['viewport'][3], 'lng');
                return;
            case 2:
                if ($params[0] === '_current_') {
                    if ((!$entity = $this->_getCurrentEntity())
                        || (!$location = $entity->getSingleFieldValue($fieldName))
                        || empty($location['lat'])
                        || empty($location['lng'])
                    ) return;
                        
                    $lat = $location['lat'];
                    $lng = $location['lng'];
                } elseif ($params[0] === '_current_user_') {
                    if ($this->_application->Location_IsSearchRequested()
                        || (!$geo = $this->_application->Location_Api_geolocateIp())
                    ) return;

                    $lat = $geo['lat'];
                    $lng = $geo['lng'];
                } else {
                    $geo = $this->_application->Location_Api_geocode($params[0], false);
                    $lat = $geo['lat'];
                    $lng = $geo['lng'];
                }
                $radius = (int)$params[1];
                break;
            default:
                $lat = $params[0];
                $lng = $params[1];
                $radius = (int)$params[2];
                break;
        }
        $query->addCriteria($this->_application->Map_IsNearbyCriteria($field, $lat, $lng, $radius));
    }
    
    public function fieldOpenGraphProperties()
    {
        return ['business:contact_data', 'place:location'];
    }
    
    public function fieldOpenGraphRenderProperty(Field\IField $field, $property, IEntity $entity)
    {
        if (!$value = $entity->getSingleFieldValue($field->getFieldName())) return;
        
        switch ($property) {
            case 'business:contact_data':
                return [
                    'business:contact_data:street_address' => $value['street'],
                    'business:contact_data:locality' => $value['city'],
                    'business:contact_data:region' => $value['province'],
                    'business:contact_data:postal_code' => $value['zip'],
                    'business:contact_data:country_name' => $value['country'],
                ];
            case 'place:location':
                return [
                    'place:location:latitude' => $value['lat'],
                    'place:location:longitude' => $value['lng'],
                ];
        }
    }
    
    public function fieldHumanReadableText(Field\IField $field, IEntity $entity, $separator = null, $key = null)
    {
        if (!$values = $entity->getFieldValue($field->getFieldName())) return '';

        if (isset($key) && in_array($key, ['street', 'street2', 'city', 'province', 'zip', 'country', 'timezone', 'zoom', 'lat', 'lng', 'address'])) {
            foreach ($values as $value) {
                $ret[] = $value[$key];
            }
        } else {
            foreach ($values as $value) {
                $ret[] = isset($value['display_address']) ? strip_tags($value['display_address']) : $value['address'];
            }
        }

        return implode(isset($separator) ? $separator : PHP_EOL, $ret);
    }
    
    public function fieldConditionableInfo(Field\IField $field, $isServerSide = false)
    {
        if (!$field->Bundle
            || (!$location_bundle = $this->_getLocationBundle($field->Bundle))
        ) return;
        
        return [
            'term_id' => [
                'compare' => ['value', '!value', 'one', 'empty', 'filled'],
                'tip' => __('Enter taxonomy term IDs and/or slugs separated with commas.', 'directories-pro'),
                'example' => '1,5,new-york',
                //'label' => $location_bundle->getLabel('singular'),
            ],
        ];
    }
    
    public function fieldConditionableRule(Field\IField $field, $compare, $value = null, $name = '')
    {
        switch ($compare) {
            case 'value':
            case '!value':
            case 'one':
                $value = trim($value);
                if (strpos($value, ',')) {
                    if (!$value = explode(',', $value)) return;
                    
                    $value = array_map('trim', $value);
                }
                return ['type' => $compare, 'value' => $value, 'target' => '.drts-location-term-select'];
            case 'empty':
                return ['type' => 'filled', 'value' => false, 'target' => '.drts-location-term-select'];
            case 'filled':
                return ['type' => 'empty', 'value' => false, 'target' => '.drts-location-term-select'];
            default:
                return;
        }
    }

    public function fieldConditionableMatch(Field\IField $field, array $rule, array $values, IEntity $entity)
    {
        switch ($rule['type']) {
            case 'value':
            case '!value':
            case 'one':
                if ($this->_isEmpty($values)) return $rule['type'] === '!value';

                foreach ((array)$rule['value'] as $rule_value) {
                    $rule_value = (int)$rule_value;
                    foreach ($values as $input) {
                        if ((int)$input['term_id'] == $rule_value) {
                            if ($rule['type'] === '!value') return false;
                            if ($rule['type'] === 'one') return true;
                            continue 2;
                        }
                    }
                    // One of rules did not match
                    if ($rule['type'] === 'value') return false;
                }
                // All rules matched or did not match.
                return $rule['type'] !== 'one' ? true : false;
            case 'empty':
                return $this->_isEmpty($values) === $rule['value'];
            case 'filled':
                return $this->_isEmpty($values) !== $rule['value'];
            default:
                return false;
        }
    }

    protected function _isEmpty(array $values)
    {
        if (empty($values)) return true;

        foreach ($values as $value) {
            if (!empty($value['term_id'])
                || strlen(trim($value['address']))
            ) return false;
        }
        return true;
    }

    protected function _getLocationBundle(Bundle $bundle)
    {
        return $this->_application->Entity_Bundle('location_location', $bundle->component, $bundle->group);
    }

    public function mapCoordinates(Field\IField $field, IEntity $entity)
    {
        if (!$value = $entity->getSingleFieldValue($field->getFieldName())) return;

        return [$value['lat'], $value['lng']];
    }

    public function fieldCopyValues(Field\IField $field, array $values, array &$allValues, $lang = null)
    {
        if (!empty($lang)) {
            if (!$field->Bundle
                || (!$location_bundle = $this->_getLocationBundle($field->Bundle))
            ) {
                $this->_application->logError('Failed fetching location field bundle.');
                return;
            }

            if ($this->_application->getPlatform()->isTranslatable($location_bundle->entitytype_name, $location_bundle->name)) {
                foreach (array_keys($values) as $k) {
                    if (empty($values[$k]['term_id'])) continue;

                    $values[$k]['term_id'] = (int)$this->_application->getPlatform()->getTranslatedId(
                        $location_bundle->entitytype_name,
                        $location_bundle->name,
                        $values[$k]['term_id'],
                        $lang
                    );
                }

            }
        }
        // Fill taxonomy term values
        foreach (array_keys($values) as $k) {
            if (empty($values[$k]['term_id'])) continue;

            $allValues['location_location'][]['value'] = $values[$k]['term_id'];
        }

        return $values;
    }
}
