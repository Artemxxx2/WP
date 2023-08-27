<?php
namespace SabaiApps\Directories\Component\Field\Filter;

class StringExistsFilter extends BooleanFilter
{
    protected $_nullOnly = true;
    
    protected function _fieldFilterInfo()
    {
        $info = parent::_fieldFilterInfo();
        $info['field_types'] = ['string', 'email', 'url', 'phone'];
        $info['default_settings']['checkbox_label'] = _x('%s field is filled out', 'checkbox filter label', 'directories');
        $info['default_settings']['hide_count'] = false;
        $info['facetable'] = true;
        return $info;
    }
}