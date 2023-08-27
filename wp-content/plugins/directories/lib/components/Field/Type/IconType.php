<?php
namespace SabaiApps\Directories\Component\Field\Type;

use SabaiApps\Directories\Application;

class IconType extends AbstractValueType
{
    protected function _fieldTypeInfo()
    {
        return array(
            'label' => __('Icon', 'directories'),
            'icon' => 'fab fa-font-awesome-alt'
        );
    }
    
    public function fieldTypeSchema()
    {
        return array(
            'columns' => array(
                'value' => array(
                    'type' => Application::COLUMN_VARCHAR,
                    'length' => 40,
                    'notnull' => true,
                    'was' => 'value',
                    'default' => '',
                ),
            ),
            'indexes' => array(
                'value' => array(
                    'fields' => array('value' => array('sorting' => 'ascending')),
                    'was' => 'value',
                ),
            ),
        );
    }
}