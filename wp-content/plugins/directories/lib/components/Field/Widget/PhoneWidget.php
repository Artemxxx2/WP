<?php
namespace SabaiApps\Directories\Component\Field\Widget;

class PhoneWidget extends TextfieldWidget
{
    protected function _fieldWidgetInfo()
    {
        $info = parent::_fieldWidgetInfo();
        $info['field_types'] = array($this->_name);
        return $info;
    }
}