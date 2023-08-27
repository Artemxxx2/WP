<?php
namespace SabaiApps\Directories\Component\Voting\FieldFilter;

use SabaiApps\Directories\Component\Field;

abstract class AbstractFieldFilter extends Field\Filter\AbstractFilter
{
    protected $_valueColumn = 'level';
    
    protected function _fieldFilterInfo()
    {
        return [
            'field_types' => array('voting_vote'),
        ];
    }
    
    public function fieldFilterIsFilterable(Field\IField $field, array $settings, &$value, array $requests = null)
    {
        return !empty($value) && (is_numeric($value) || is_array($value));
    }
    
    public function fieldFilterDoFilter(Field\Query $query, Field\IField $field, array $settings, $value, array &$sorts)
    {
        $vote_name = $this->_getVoteName($settings);
        $alias = $field->getFieldName() . '__' . $vote_name;
        $query->fieldIs($field, $vote_name, 'name', $alias, null, $alias);
        if (is_array($value)) {
            $query->fieldIsIn($field, $value, $this->_valueColumn, $alias, null, $alias);
        } else {
            $query->fieldIsOrGreaterThan($field, $value, $this->_valueColumn, $alias, null, $alias);
        }
    }
    
    public function fieldFilterLabels(Field\IField $field, array $settings, $value, $form, $defaultLabel)
    {
        $labels = [];
        if (isset($settings['type'])
            && $settings['type'] === 'checkboxes'
        ) {
            foreach ((array)$value as $_value) {
                $_value = (int)$_value;
                $labels[] = sprintf(_n('%d star', '%d stars', $_value, 'directories'), $_value);
            }
            $label = implode(' ', $labels);
        } else {
            $label = sprintf($value === 5 ? _n('%d star', '%d stars', $value, 'directories') : __('%d+ stars', 'directories'), $value);
        }

        return [
            '' => $this->_application->H($label),
        ];
    }
    
    protected function _getVoteName(array $settings)
    {
        return '';
    }
}