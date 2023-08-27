<?php
namespace SabaiApps\Directories\Component\Map\Helper;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Exception\InvalidArgumentException;
use SabaiApps\Framework\Criteria\IsOrSmallerThanCriteria;
use SabaiApps\Directories\Component\Field\IField;

class IsNearbyCriteriaHelper
{    
    public function help(Application $application, $field, $lat, $lng, $radius = 100)
    {
        if ($field instanceof IField) {
            $field_type = $field->getFieldType();
            $field_name = $field->getFieldName();
        } elseif (is_array($field)) {
            if (empty($field[0])
                || empty($field[1])
            ) throw new InvalidArgumentException();

            $field_type = $field[0];
            $field_name = $field[1];
        } elseif (is_string($field)) {
            $field_type = $field_name = $field;
        } else {
            throw new InvalidArgumentException();
        }

        $target = array(
            'tables' => array(
                $application->getDB()->getResourcePrefix() . 'entity_field_' . $field_type  => array(
                    'alias' => $field_type,
                    'on' => null,
                    'field_name' => $field_name,
                ),
            ),
            'column' => sprintf(
                '(%1$d * acos(cos(radians(%2$.6F)) * cos(radians(%4$s.lat)) * cos(radians(%4$s.lng) - radians(%3$.6F)) + sin(radians(%2$.6F)) * sin(radians(%4$s.lat))))',
                $application->getComponent('Map')->getConfig('map', 'distance_unit') === 'mi' ? 3959 : 6371,
                $lat,
                $lng,
                $field_type
            ),
            'column_type' => Application::COLUMN_DECIMAL,
        );
        
        return new IsOrSmallerThanCriteria($target, $radius);
    }
}