<?php
namespace SabaiApps\Directories\Component\Entity\FieldType;

use SabaiApps\Directories\Component\Field;

class FieldType extends Field\Type\AbstractType
{
    protected function _fieldTypeInfo()
    {
        switch ($this->_name) {
            case 'entity_bundle_name':
                return array(
                    'label' => 'Content type',
                    'creatable' => false,
                );
            case 'entity_bundle_type':
                return array(
                    'label' => 'Content type',
                    'creatable' => false,
                );
        }
    }
}