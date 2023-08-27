<?php
namespace SabaiApps\Directories\Component\Payment\FieldType;

use SabaiApps\Directories\Component\Field;

class OrdersFieldType extends Field\Type\AbstractType
{
    protected function _fieldTypeInfo()
    {
        return array(
            'label' => __('Orders', 'directories-payments'),
            'creatable' => false,
            'admin_only' => true,
        );
    }
}