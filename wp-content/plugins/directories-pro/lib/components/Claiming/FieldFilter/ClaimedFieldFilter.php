<?php
namespace SabaiApps\Directories\Component\Claiming\FieldFilter;

use SabaiApps\Directories\Component\Field\Filter\BooleanFilter;

class ClaimedFieldFilter extends BooleanFilter
{
    protected $_nullOnly = true;

    protected function _fieldFilterInfo()
    {
        $info = parent::_fieldFilterInfo();
        $info['field_types'] = ['entity_author'];
        $info['default_settings']['checkbox_label'] = __('Show claimed only', 'directories-pro');
        return $info;
    }
}