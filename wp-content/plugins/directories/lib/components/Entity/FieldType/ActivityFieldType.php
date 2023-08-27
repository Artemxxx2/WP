<?php
namespace SabaiApps\Directories\Component\Entity\FieldType;

use SabaiApps\Directories\Component\Field;
use SabaiApps\Directories\Application;

class ActivityFieldType extends Field\Type\AbstractType implements Field\Type\ISortable
{
    protected function _fieldTypeInfo()
    {
        return array(
            'label' => _x('Activity', 'content activity', 'directories'),
            'creatable' => false,
            'icon' => 'far fa-calendar-check',
        );
    }

    public function fieldTypeSchema()
    {
        return array(
            'columns' => array(
                'active_at' => array(
                    'type' => Application::COLUMN_INTEGER,
                    'notnull' => true,
                    'unsigned' => true,
                    'was' => 'active_at',
                    'default' => 0,
                ),
                'edited_at' => array(
                    'type' => Application::COLUMN_INTEGER,
                    'notnull' => true,
                    'unsigned' => true,
                    'was' => 'edited_at',
                    'default' => 0,
                ),
                'active_post_id' => array(
                    'type' => Application::COLUMN_INTEGER,
                    'notnull' => true,
                    'unsigned' => true,
                    'was' => 'active_post_id',
                    'default' => 0,
                ),
            ),
            'indexes' => array(
                'active_at' => array(
                    'fields' => array('active_at' => array('sorting' => 'ascending')),
                    'was' => 'active_at',
                ),
                'edited_at' => array(
                    'fields' => array('edited_at' => array('sorting' => 'ascending')),
                    'was' => 'edited_at',
                ),
            ),
        );
    }

    public function fieldTypeOnSave(Field\IField $field, array $values, array $currentValues = null, array &$extraArgs = [])
    {
        if (!isset($values[0]) || !is_array($values[0])) return array(false); // remove

        return $values;
    }
    
    public function fieldSortableOptions(Field\IField $field)
    {
        return array(
            array('label' => __('Recently Active', 'directories')),
        );
    }
    
    public function fieldSortableSort(Field\Query $query, $fieldName, array $args = null)
    {
        $query->sortByField($fieldName, isset($args) && $args[0] === 'asc' ? 'ASC' : 'DESC', 'active_at');
    }
}