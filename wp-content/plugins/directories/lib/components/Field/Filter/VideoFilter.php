<?php
namespace SabaiApps\Directories\Component\Field\Filter;

class VideoFilter extends BooleanFilter
{
    protected $_filterColumn = null, $_nullOnly = true;
    
    protected function _fieldFilterInfo()
    {
        return [
            'field_types' => [$this->_name],
            'default_settings' => [
                'checkbox_label' => __('Show with video only', 'directories'),
                'hide_count' => false,
            ],
            'facetable' => true,
        ];
    }
}