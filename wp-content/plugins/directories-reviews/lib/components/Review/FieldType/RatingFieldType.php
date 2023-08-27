<?php
namespace SabaiApps\Directories\Component\Review\FieldType;

use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Field;
use SabaiApps\Directories\Application;

class RatingFieldType extends Field\Type\AbstractType implements
    Field\Type\ISchemable,
    Field\Type\ISortable,
    Field\Type\ICopiable
{
    protected function _fieldTypeInfo()
    {
        return array(
            'label' => __('Review Rating', 'directories-reviews'),
            'creatable' => false,
            'disablable' => false,
            'icon' => 'fas fa-star',
        );
    }

    public function fieldTypeSchema()
    {
        return array(
            'columns' => array(
                'value' => array(
                    'type' => Application::COLUMN_DECIMAL,
                    'notnull' => true,
                    'length' => 5,
                    'scale' => 2,
                    'unsigned' => true,
                    'was' => 'value',
                    'default' => 0,
                ),
                'name' => array(
                    'type' => Application::COLUMN_VARCHAR,
                    'notnull' => true,
                    'length' => 50,
                    'was' => 'name',
                    'default' => '',
                ),
                'level' => array(
                    'type' => Application::COLUMN_INTEGER,
                    'notnull' => true,
                    'was' => 'level',
                    'default' => 0,
                    'length' => 5,
                ),
            ),
            'indexes' => array(
                'name_value' => array(
                    'fields' => array(
                        'name' => array('sorting' => 'ascending'),
                        'value' => array('sorting' => 'ascending')
                    ),
                    'was' => 'name_value',
                ),
                'level' => array(
                    'fields' => array('level' => array('sorting' => 'ascending')),
                    'was' => 'level',
                ),
            ),
        );        
    }

    public function fieldTypeOnSave(Field\IField $field, array $values, array $currentValues = null, array &$extraArgs = [])
    {
        $value = array_shift($values);
        foreach ($value as $name => $rating) {
            if (!is_numeric($rating)
                || $rating > 5
                || $rating < 0
            ) {
                unset($value[$name]);
                continue;
            }
            $rating = (float)$rating;
            if ($rating >= 0 && $rating <= 5) {
                $ret[$name] = array(
                    'name' => $name,
                    'value' => $rating,
                    'level' => (int)round($rating)
                );
            }
        }
        if (!isset($ret['_all'])) {
            $_value = ($count = count($value)) ? round(array_sum($value) / $count, 1) : 0;
            $ret['_all'] = array(
                'name' => '_all',
                'value' => $_value,
                'level' => (int)round($_value)
            );
        }
        ksort($ret);
        return array_values($ret);
    }

    public function fieldTypeOnLoad(Field\IField $field, array &$values, Entity\Type\IEntity $entity, array $allValues)
    {
        $new_values = [];
        foreach ($values as $value) {
            // Index by rating name
            $_value = $value;
            unset($_value['name']);
            $new_values[$value['name']] = $_value;
            $new_values[$value['name']]['value'] = (float)$new_values[$value['name']]['value'];
        }
        $values = array($new_values);
    }
    
    public function fieldTypeIsModified(Field\IField $field, $valueToSave, $currentLoadedValue)
    {
        if (empty($currentLoadedValue[0])) return true;
            
        $current = [];
        foreach (array_keys($currentLoadedValue[0]) as $name) {
            $current[] = $currentLoadedValue[0][$name] + array('name' => $name);
        }
        foreach (array_keys($current) as $key) {
            if (count($current[$key]) !== count($valueToSave[$key])
                || array_diff_assoc($current[$key], $valueToSave[$key])
            ) return true;
        }
        return false;
    }
    
    public function fieldSchemaProperties()
    {
        return array('reviewRating');
    }
    
    public function fieldSchemaRenderProperty(Field\IField $field, $property, Entity\Type\IEntity $entity)
    {
        if (!$value = $entity->getSingleFieldValue($field->getFieldName())) return;
        
        return array(array(
            '@type' => 'Rating',
            'ratingValue' => $value['_all']['value'],
            'bestRating' => 5,
            'worstRating' => 1,
        ));
    }
    
    public function fieldSortableOptions(Field\IField $field)
    {
        return [
            ['label' => $label = __('Review Rating', 'directories-reviews')],
            ['args' => ['asc'], 'label' => sprintf(__('%s (asc)', 'directories-reviews'), $label)],
        ];
    }
    
    public function fieldSortableSort(Field\Query $query, $fieldName, array $args = null)
    {
        $query->sortByField(
            $fieldName,
            isset($args[0]) && $args[0] === 'asc' ? 'ASC' : 'DESC',
            'value',
            null,
            0,
            'name',
            '_all'
        );
    }

    public function fieldCopyValues(Field\IField $field, array $values, array &$allValue, $lang = null)
    {
        return $values;
    }
}