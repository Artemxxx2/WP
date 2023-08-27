<?php
namespace SabaiApps\Directories\Component\Map\FieldType;

use SabaiApps\Directories\Component\Field;
use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Application;

class MapFieldType extends Field\Type\AbstractType implements
    Field\Type\ISortable,
    Field\Type\ISchemable,
    Field\Type\IQueryable,
    Field\Type\IOpenGraph,
    ICoordinates,
    Field\Type\ICopiable
{
    protected function _fieldTypeInfo()
    {
        return [
            'label' => __('Map', 'directories'),
            'icon' => 'far fa-map',
        ];
    }

    public function fieldTypeSchema()
    {
        return [
            'columns' => [
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

    public function fieldTypeOnSave(Field\IField $field, array $values, array $currentValues = null, array &$extraArgs = [])
    {
        $ret = [];
        foreach ($values as $weight => $value) {
            if (!is_array($value)) continue;

            unset($value['_latlng']); // this may be sent from form
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
                    unset($value['lat'], $value['lng'], $value['zoom']);
                }
            } else {
                unset($value['lat'], $value['lng'], $value['zoom']);
            }
            if ($value = array_filter($value)) {
                $ret[] = $value;
            }
        }

        return $ret;
    }

    public function fieldTypeOnLoad(Field\IField $field, array &$values, Entity\Type\IEntity $entity, array $allValues)
    {
        foreach (array_keys($values) as $key) {
            settype($values[$key]['lat'], 'float');
            settype($values[$key]['lng'], 'float');
        }
    }
    
    public function fieldSortableOptions(Field\IField $field)
    {
        return [
            ['label' => __('Distance', 'directories')],
        ];
    }

    public function fieldSortableSort(Field\Query $query, $fieldName, array $args = null)
    {
        $config = $this->_application->getComponent('Map')->getConfig('map');
        if (isset($args[1])
            && isset($args[2])
        ) {
            $lat = $args[1];
            $lng = $args[2];
        } else {
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
        $query->addExtraField('distance', $fieldName, $sql, true, true)
            ->sortByExtraField('distance', isset($args[0]) && $args[0] === 'desc' ? 'DESC' : 'ASC');
    }
    
    public function fieldSchemaProperties()
    {
        return ['geo'];
    }
    
    public function fieldSchemaRenderProperty(Field\IField $field, $property, Entity\Type\IEntity $entity)
    {
        if (!$value = $entity->getFieldValue($field->getFieldName())) return;
     
        $ret = [];
        switch ($property) {
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
        return [
            'example' => '40.69847,-73.95144,10',
            'tip' => __('Enter three values (latitude, longitude, radius) separated with commas to query by coordinates.', 'directories'),
        ];
    }
    
    public function fieldQueryableQuery(Field\Query $query, $fieldName, $paramStr, Entity\Model\Bundle $bundle)
    {
        $params = $this->_queryableParams($paramStr);

        // Check if field exists if "1" passed, used by Map view mode
        if (count($params) === 1) {
            if ($params[0] == 1)  {
                $query->fieldIsNotNull($fieldName, 'lat')
                    ->fieldIsNot($fieldName, 0, 'lat')
                    ->fieldIsNot($fieldName, 0, 'lng');;
            }
            return;
        }

        if (count($params) !== 3
            || (!$field = $this->_application->Entity_Field($bundle, $fieldName))
        ) return;

        $lat = $params[0];
        $lng = $params[1];
        $radius = (int)$params[2];
        $query->addCriteria($this->_application->Map_IsNearbyCriteria($field, $lat, $lng, $radius));
    }
    
    public function fieldOpenGraphProperties()
    {
        return ['place:location'];
    }
    
    public function fieldOpenGraphRenderProperty(Field\IField $field, $property, Entity\Type\IEntity $entity)
    {
        if (!$value = $entity->getSingleFieldValue($field->getFieldName())) return;
        
        switch ($property) {
            case 'place:location':
                return [
                    'place:location:latitude' => $value['lat'],
                    'place:location:longitude' => $value['lng'],
                ];
        }
    }

    public function mapCoordinates(Field\IField $field, Entity\Type\IEntity $entity)
    {
        if (!$value = $entity->getSingleFieldValue($field->getFieldName())) return;

        return [$value['lat'], $value['lng']];
    }

    public function fieldCopyValues(Field\IField $field, array $values, array &$allValue, $lang = null)
    {
        return $values;
    }
}