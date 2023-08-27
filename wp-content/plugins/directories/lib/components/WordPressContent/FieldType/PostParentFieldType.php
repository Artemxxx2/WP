<?php
namespace SabaiApps\Directories\Component\WordPressContent\FieldType;

use SabaiApps\Directories\Component\Entity;

class PostParentFieldType extends Entity\FieldType\ParentFieldType
{
    protected function _fieldTypeInfo()
    {
        return array(
            'label' => __('Parent Post', 'directories'),
            'entity_types' => array('post'),
        ) + parent::_fieldTypeInfo();
    }
}