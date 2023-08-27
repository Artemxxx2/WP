<?php
namespace SabaiApps\Directories\Component\WordPressContent\FieldFilter;

use SabaiApps\Directories\Component\Field;

class ImageFieldFilter extends Field\Filter\BooleanFilter
{
    protected $_filterColumn = null, $_nullOnly = true;
    
    protected function _fieldFilterInfo()
    {
        return [
            'label' => _x('Image', 'field type', 'directories'),
            'field_types' => [$this->_name],
            'default_settings' => [
                'checkbox_label' => __('Show with image only', 'directories'),
                'hide_count' => false,
            ],
            'facetable' => true,
        ];
    }
}