<?php
namespace SabaiApps\Directories\Component\Entity\FieldType;

use SabaiApps\Directories\Component\Field;

class TermParentFieldType extends Field\Type\AbstractType implements Field\Type\IQueryable
{
    use QueryableTermsTrait;

    protected function _fieldTypeInfo()
    {
        return array(
            'label' => __('Parent Term', 'directories'),
            'entity_types' => array('term'),
            'creatable' => false,
            'icon' => 'fas fa-sitemap',
        );
    }
}