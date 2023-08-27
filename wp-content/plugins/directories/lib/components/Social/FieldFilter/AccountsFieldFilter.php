<?php
namespace SabaiApps\Directories\Component\Social\FieldFilter;

use SabaiApps\Directories\Component\Field;

class AccountsFieldFilter extends Field\Filter\OptionFilter
{
    protected $_valueColumn = 'media', $_labelSortable = false;
    
    protected function _fieldFilterInfo()
    {
        return array(
            'label' => __('Social Accounts', 'directories'),
            'field_types' => array('social_accounts'),
        ) + parent::_fieldFilterInfo();
    }
    
    protected function _getOptions(Field\IField $field, array $settings, &$noEscape = false)
    {
        $ret = [];
        $noEscape = true;
        $field_settings = $field->getFieldSettings();
        foreach ($this->_application->Social_Medias() as $media_name => $media) {
            if (!empty($field_settings['medias'])
                && !in_array($media_name, $field_settings['medias'])
            ) continue;
            
            $ret[$media_name] = '<i class="fa-fw ' . $media['icon'] . '"></i> ' . $this->_application->H($media['label']);
        }
        return $ret;
    }
    
    protected function _isMultipleChoiceField(Field\IField $field)
    {
        return true;
    }
}