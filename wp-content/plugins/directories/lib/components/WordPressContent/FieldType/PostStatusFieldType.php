<?php
namespace SabaiApps\Directories\Component\WordPressContent\FieldType;

use SabaiApps\Directories\Component\Field;

class PostStatusFieldType extends Field\Type\AbstractType
{
    protected function _fieldTypeInfo()
    {
        switch ($this->_name) {
            case 'wp_post_status':
                return array(
                    'label' => 'Status',
                    'entity_types' => array('post'),
                    'creatable' => false,
                    'admin_only' => true,
                );
        }
    }
}
