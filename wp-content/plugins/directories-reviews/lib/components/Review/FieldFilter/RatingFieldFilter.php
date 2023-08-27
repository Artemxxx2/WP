<?php
namespace SabaiApps\Directories\Component\Review\FieldFilter;

use SabaiApps\Directories\Component\Field;
use SabaiApps\Directories\Component\Entity;

class RatingFieldFilter extends Field\Filter\AbstractFilter
{
    protected function _fieldFilterInfo()
    {
        return array(
            'field_types' => [$this->_name],
            'default_settings' => array(
                'name' => '_all',
            ),
        );
    }
    
    public function fieldFilterIsFilterable(Field\IField $field, array $settings, &$value, array $requests = null)
    {
        return !empty($value) && is_numeric($value);
    }
    
    public function fieldFilterDoFilter(Field\Query $query, Field\IField $field, array $settings, $value, array &$sorts)
    {
        $query->fieldIs($field, isset($settings['name']) ? $settings['name'] : '_all', 'name')
            ->fieldIsOrGreaterThan($field, $value, 'level');
    }
    
    public function fieldFilterForm(Field\IField $field, $filterName, array $settings, $request = null, Entity\Type\Query $query = null, array $current = null, $autoSubmit = true, array $parents = array())
    {
        return [
            '#type' => 'select',
            '#options' => $this->_application->Voting_RenderRating_options(true, ''),
            '#empty_value' => '',
            '#entity_filter_form_type' => 'select',
        ];
    }
}