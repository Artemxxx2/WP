<?php
namespace SabaiApps\Directories\Component\Claiming\FieldType;

use SabaiApps\Directories\Component\Field\Type\AbstractValueType;
use SabaiApps\Directories\Component\Field\Type\IColumnable;
use SabaiApps\Directories\Component\Field\Type\IConditionable;
use SabaiApps\Directories\Component\Field\Type\IRestrictable;
use SabaiApps\Directories\Component\Field\IField;
use SabaiApps\Directories\Component\Entity\Type\IEntity;
use SabaiApps\Directories\Application;

class StatusFieldType extends AbstractValueType implements IColumnable, IRestrictable, IConditionable
{
    protected function _fieldTypeInfo()
    {
        return array(
            'label' => __('Claim Status', 'directories-pro'),
            'creatable' => false,
            'admin_only' => true,
        );
    }
    
    public function fieldTypeOnSave(IField $field, array $values, array $currentValues = null, array &$extraArgs = [])
    {
        if (!$this->_application->HasPermission('entity_delete_others_' . $field->Bundle->name)) return;
        
        return ($ret = parent::fieldTypeOnSave($field, $values, $currentValues, $extraArgs)) ? $ret : null;
    }

    public function fieldTypeSchema()
    {
        return array(
            'columns' => array(
                'value' => array(
                    'type' => Application::COLUMN_VARCHAR,
                    'length' => 10,
                    'notnull' => true,
                    'was' => 'value',
                    'default' => '',
                ),
            ),
            'indexes' => array(
                'value' => array(
                    'fields' => array('value' => array('sorting' => 'ascending')),
                    'was' => 'value',
                ),
            ),
        );
    }
    
    public function fieldColumnableInfo(IField $field)
    {
        return [
            '' => [
                'label' => $field->getFieldLabel(),
                'render_empty' => true,
            ],
        ];
    }
    
    public function fieldColumnableColumn(IField $field, $value, $column = '')
    {
        $status = isset($value[0]) ? $value[0] : null;
        $statuses = $this->_application->Claiming_Statuses();
        if (!isset($statuses[$status])) {
            $label = __('Pending', 'directories-pro');
            $color = 'warning';
        } else {
            $label = $statuses[$status]['label'];
            $color = $statuses[$status]['color'];
        }
        return '<span class="' . DRTS_BS_PREFIX . 'badge ' . DRTS_BS_PREFIX . 'badge-' . $color . '">' . $this->_application->H($label) . '</span>';
    }
    
    public function fieldRestrictableOptions(IField $field)
    {
        $ret = [];
        foreach ($this->_application->Claiming_Statuses() as $status => $status_info) {
            $ret[$status] = $status_info['label'];
        }
        $ret['pending'] = __('Pending', 'directories-pro');
        return $ret;
    }
    
    public function fieldRestrictableRestrict(IField $field, $value)
    {
        return ($value === 'pending') ? array('compare' => 'NULL') : [];
    }

    public function fieldConditionableInfo(IField $field, $isServerSide = false)
    {
        return [
            '' => [
                'compare' => ['value', '!value'],
                'tip' => __('Enter "approved" or "rejected".', 'directories-pro'),
            ],
        ];
    }

    public function fieldConditionableRule(IField $field, $compare, $value = null, $name = '')
    {
        switch ($compare) {
            case 'value':
            case '!value':
                return ['type' => $compare, 'value' => $value];
            default:
                return;
        }
    }

    public function fieldConditionableMatch(IField $field, array $rule, array $values, IEntity $entity)
    {
        switch ($rule['type']) {
            case 'value':
            case '!value':
                if (empty($values)) return $rule['type'] === '!value';

                foreach ((array)$rule['value'] as $rule_value) {
                    foreach ($values as $input) {
                        if (is_array($input)) {
                            $input = $input[isset($this->_valueColumn) ? $this->_valueColumn : 'value'];
                        }
                        if ($input == $rule_value) {
                            if ($rule['type'] === '!value') return false;
                            continue 2;
                        }
                    }
                    // One of rules did not match
                    if ($rule['type'] === 'value') return false;
                }
                // All rules matched or did not match.
                return true;
            default:
                return false;
        }
    }
}