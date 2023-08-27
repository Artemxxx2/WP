<?php
namespace SabaiApps\Directories\Component\Field\Widget;

use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Field\IField;

class ColorWidget extends AbstractWidget
{    
    protected function _fieldWidgetInfo()
    {
        return array(
            'label' => __('Color picker', 'directories'),
            'field_types' => array($this->_name),
            'repeatable' => true,
        );
    }

    public function fieldWidgetForm(IField $field, array $settings, $value = null, Entity\Type\IEntity $entity = null, array $parents = [], $language = null)
    {
        return array(
            '#type' => 'colorpicker',
            '#default_value' => isset($value) ? $value : null,
        );
    }
}