<?php
namespace SabaiApps\Directories\Component\Location\FieldWidget;

use SabaiApps\Directories\Component\Entity;

class SelectFieldWidget extends Entity\FieldWidget\TermSelectFieldWidget
{
    protected function _fieldWidgetInfo()
    {
        $info = parent::_fieldWidgetInfo();
        $info['field_types'] = ['location_address'];
        return $info;
    }

    public function fieldWidgetSupports($fieldOrFieldType)
    {
        return is_object($fieldOrFieldType)
            && ($bundle = $fieldOrFieldType->Bundle)
            && !empty($bundle->info['location_enable']);
    }

    protected function _getTaxonomyBundle($field)
    {
        if (!isset($field->Bundle->info['taxonomies']['location_location'])) return;

        return $this->_application->Entity_Bundle($field->Bundle->info['taxonomies']['location_location']);
    }

    protected function _getTaxonomyTermId($value)
    {
        return empty($value['term_id']) ? null : $value['term_id'];
    }
}