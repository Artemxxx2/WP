<?php
namespace SabaiApps\Directories\Component\Voting\FieldType;

use SabaiApps\Directories\Component\Field;
use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Application;

class FieldType extends Field\Type\AbstractType implements
    Field\Type\ISortable,
    Field\Type\IColumnable,
    Field\Type\IQueryable,
    Field\Type\IConditionable
{
    protected function _fieldTypeInfo()
    {
        return [
            'label' => __('Votes', 'directories'),
            'creatable' => false,
            'icon' => 'fas fa-star',
            'admin_only' => true,
        ];
    }

    public function fieldTypeSchema()
    {
        return [
            'columns' => [
                'count' => [
                    'type' => Application::COLUMN_INTEGER,
                    'notnull' => true,
                    'was' => 'count',
                    'default' => 0,
                    'length' => 10,
                ],
                'sum' => [
                    'type' => Application::COLUMN_DECIMAL,
                    'notnull' => true,
                    'length' => 5,
                    'scale' => 2,
                    'unsigned' => false,
                    'was' => 'sum',
                    'default' => 0,
                ],
                'average' => [
                    'type' => Application::COLUMN_DECIMAL,
                    'notnull' => true,
                    'length' => 5,
                    'scale' => 2,
                    'unsigned' => false,
                    'was' => 'average',
                    'default' => 0,
                ],
                'last_voted_at' => [
                    'type' => Application::COLUMN_INTEGER,
                    'notnull' => true,
                    'was' => 'last_voted_at',
                    'default' => 0,
                    'length' => 10,
                ],
                'name' => [
                    'type' => Application::COLUMN_VARCHAR,
                    'length' => 40,
                    'notnull' => true,
                    'was' => 'name',
                    'default' => '',
                ],
                'count_init' => [
                    'type' => Application::COLUMN_INTEGER,
                    'notnull' => true,
                    'unsigned' => false,
                    'was' => 'count_init',
                    'default' => 0,
                    'length' => 10,
                ],
                'sum_init' => [
                    'type' => Application::COLUMN_DECIMAL,
                    'notnull' => true,
                    'length' => 5,
                    'scale' => 2,
                    'unsigned' => false,
                    'was' => 'sum_init',
                    'default' => 0,
                ],
                'level' => [
                    'type' => Application::COLUMN_INTEGER,
                    'notnull' => true,
                    'was' => 'level',
                    'default' => 0,
                    'length' => 10,
                ],
            ],
            'indexes' => [
                'count' => [
                    'fields' => ['count' => ['sorting' => 'ascending']],
                    'was' => 'count',
                ],
                'sum' => [
                    'fields' => ['sum' => ['sorting' => 'ascending']],
                    'was' => 'sum',
                ],
                'average' => [
                    'fields' => ['average' => ['sorting' => 'ascending']],
                    'was' => 'average',
                ],
                'last_voted_at' => [
                    'fields' => ['last_voted_at' => ['sorting' => 'ascending']],
                    'was' => 'last_voted_at',
                ],
                'name' => [
                    'fields' => ['name' => ['sorting' => 'ascending']],
                    'was' => 'name',
                ],
                'level' => [
                    'fields' => ['level' => ['sorting' => 'ascending']],
                    'was' => 'level',
                ],
            ],
        ];
    }

    public function fieldTypeOnSave(Field\IField $field, array $values, array $currentValues = null, array &$extraArgs = [])
    {
        $ret = [];
        foreach ($values as $value) {
            if (!is_array($value)) continue;
            
            if (!isset($value['name'])
                || !strlen(trim($value['name']))
            ) {
                $value['name'] = '';
            }
            if (empty($value['count_init'])) {
                $value['count_init'] = $value['sum_init'] = 0;
            }
            if (isset($currentValues[0][$value['name']])) {
                if (empty($value['force'])) { // used by Faker generator to force count_init/sum_init 
                    // The following values may not be updated
                    if (isset($currentValues[0][$value['name']]['count_init'])) {
                        $value['count_init'] = $currentValues[0][$value['name']]['count_init'];
                    }
                    if (isset($currentValues[0][$value['name']]['sum_init'])) {
                        $value['sum_init'] = $currentValues[0][$value['name']]['sum_init'];
                    }
                } else {
                    unset($currentValues[0][$value['name']]);
                }
            }
 
            // Increment count/sum
            if (!empty($value['count_init'])) {
                $value['count'] += $value['count_init'];
            }
            if (!empty($value['sum_init'])) {
                $value['sum'] += $value['sum_init'];
            }
            
            if (empty($value['count'])) continue; // no votes
 
            if (empty($value['sum'])) {
                $value['average'] = 0.00;
                $value['level'] = 0;
            } else {
                $value['average'] = round($value['sum'] / $value['count'], 2);
                $value['level'] = round($value['average']);
            }
            
            $ret[$value['name']] = $value;
        }
        
        if (empty($ret)) {
            if (isset($values[0])
                && $values[0] === false
            ) {
                return [false];
            }
            if (!empty($currentValues[0])) {
                // Preserve current entry if count_init is configured
                foreach ($currentValues[0] as $name => $current) {
                    if (empty($current['count_init'])) continue;

                    $ret[$name] = [
                        'count' => $current['count_init'],
                        'sum' => $current['sum_init'],
                        'average' => round($current['sum_init'] / $current['count_init'], 2),
                    ] + $current;
                }
            }
        }
        
        return array_values($ret);        
    }

    public function fieldTypeOnLoad(Field\IField $field, array &$values, Entity\Type\IEntity $entity, array $allValues)
    {
        $new_values = [];
        foreach ($values as $value) {
            // Index by vote name
            $_value = $value;
            unset($_value['name']);
            $new_values[$value['name']] = $_value;
        }
        $values = [$new_values];
    }
    
    public function fieldTypeIsModified(Field\IField $field, $valueToSave, $currentLoadedValue)
    {
        if (empty($currentLoadedValue[0])) return true;
            
        $current = [];
        foreach (array_keys($currentLoadedValue[0]) as $name) {
            $current[] = $currentLoadedValue[0][$name] + ['name' => $name];
        }
        return $current !== $valueToSave;
    }
    
    public function fieldSortableOptions(Field\IField $field)
    {
        $field_name = $field->getFieldName();
        if (strpos($field_name, 'voting_') === 0) {
            $type = substr($field_name, 7/*strlen('voting_')*/);
            if (!$type_impl = $this->_application->Voting_Types_impl($type, true)) return false;

            if (!$label = $type_impl->votingTypeInfo('label_field')) {
                $label = $type_impl->votingTypeInfo('label');
            }
        } else {
            $label = $field->getFieldLabel(true);
        }
        return [
            ['label' => $label],
            ['args' => ['asc'], 'label' => sprintf(__('%s (asc)', 'directories'), $label)],
        ];
    }
    
    public function fieldSortableSort(Field\Query $query, $fieldName, array $args = null)
    {
        $extra_column = $extra_column_value = null;
        switch ($fieldName) {
            case 'voting_updown':
                $column = 'sum';
                break;
            case 'voting_bookmark':
                $column = 'count';
                break;
            case 'voting_rating':
                $column = 'average';
                break;
            default:
                // Todo call Voting_Type and then let the implementation set additional criteria
                $column = 'average';
                $extra_column = 'name';
                $extra_column_value = '_all';
        }
        $query->sortByField(
            $fieldName,
            isset($args) && $args[0] === 'asc' ? 'ASC' : 'DESC',
            $column,
            null,
            0,
            $extra_column,
            $extra_column_value
        );
    }

    public function fieldQueryableInfo(Field\IField $field, $inAdmin = false)
    {
        return [
            'example' => '1,10',
            'tip' => __('Enter a single number for exact match, two numbers separated with a comma for range search.', 'directories'),
        ];
    }

    public function fieldQueryableQuery(Field\Query $query, $fieldName, $paramStr, Entity\Model\Bundle $bundle)
    {
        $params = $this->_queryableParams($paramStr, false);
        switch ($fieldName) {
            case 'voting_updown':
                $column = 'sum';
                break;
            case 'voting_bookmark':
                $column = 'count';
                break;
            case 'voting_rating':
                $column = 'average';
                break;
            default:
                $query->fieldIs($fieldName, isset($params[2]) && strlen($params[2]) ? $params[2] : '_all', 'name');
                $column = 'average';
        }
        switch (count($params)) {
            case 0:
                break;
            case 1:
                if (strlen($params[0])) {
                    $query->fieldIs($fieldName, $params[0], $column);
                }
                break;
            default:
                if (strlen($params[0])) {
                    if (strlen($params[1])
                        && $params[0] === $params[1]
                    ) {
                        $query->fieldIs($fieldName, $params[0], $column);
                        $params[1] = '';
                    } else {
                        $query->fieldIsOrGreaterThan($fieldName, $params[0], $column);
                    }
                }
                if (strlen($params[1])) {
                    $query->fieldIsOrSmallerThan($fieldName, $params[1], $column);
                }
        }
    }

    public function fieldColumnableInfo(Field\IField $field)
    {
        if (strpos($field->getFieldName(), 'voting_') !== 0) return;
        
        $type = substr($field->getFieldName(), 7/*strlen('voting_')*/);
        if (!$type_impl = $this->_application->Voting_Types_impl($type, true)) return;
            
        $type_info = $type_impl->votingTypeInfo();
        return [
            '' => [
                'icon' => $type_info['icon'],
                'label' => $type_info['label'],
                'sortby' => 'count',
            ],
        ];
    }
    
    public function fieldColumnableColumn(Field\IField $field, $value, $column = '')
    {
        if (empty($value[0])) return ''; 
        
        $type = substr($field->getFieldName(), 7/*strlen('voting_')*/);
        if (!$type_impl = $this->_application->Voting_Types_impl($type, true)) return;
        
        if (isset($value[0][''])) {
            $value = $value[0][''];
        } else {
            $value = ['count' => 0, 'sum' => 0, 'average' => 0, 'level' => 0];
        }
        return $type_impl->votingTypeFormat($value, 'column');
    }

    public function fieldConditionableInfo(Field\IField $field, $isServerSide = false)
    {
        if (!$isServerSide) return;

        $ret = [
            'count' => [
                'label' => $field->getFieldLabel(),
                'compare' => ['<value', '>value', '<>value', 'empty', 'filled'],
                'tip' => __('Enter a single numeric value or multiple numeric values separated with a comma', 'directories'),
                'example' => '10',
            ],
        ];
        if (in_array($field->getFieldName(), [
            'voting_rating',
            'review_ratings',
        ])) {
            $ret['count']['label'] .= ' - ' . __('Count', 'directories');
            $ret['average'] = [
                'label' => $field->getFieldLabel(),
                'compare' => ['<value', '>value', '<>value'],
                'tip' => __('Enter a single numeric value or multiple numeric values separated with a comma', 'directories'),
                'example' => '3.5,5',
            ];
        }
        return $ret;
    }

    public function fieldConditionableRule(Field\IField $field, $compare, $value = null, $name = '')
    {
        switch ($compare) {
            case '<value':
            case '>value':
                return is_numeric($value) ? ['type' => $compare, 'value' => $value] : null;
            case '<>value':
                if (strpos($value, ',')
                    && ($values = explode(',', $value))
                    && is_numeric($values[0])
                    && is_numeric($values[1])
                ) {
                    return ['type' => $compare, 'value' => $values[0] . ',' . $values[1]];
                }
            case 'empty':
                return ['type' => 'filled', 'value' => false];
            case 'filled':
                return ['type' => 'empty', 'value' => false];
            default:
        }
    }

    public function fieldConditionableMatch(Field\IField $field, array $rule, array $values, Entity\Type\IEntity $entity)
    {
        switch ($rule['type']) {
            case '<value':
            case '>value':
                if (!empty($values)) {
                    $key1 = '_all';
                    $key2 = $rule['name'];
                    foreach ($values as $input) {
                        foreach ((array)$rule['value'] as $rule_value) {
                            if ($rule['type'] === '<value') {
                                if ($input[$key1][$key2] < $rule_value) return true;
                            } else {
                                if ($input[$key1][$key2] > $rule_value) return true;
                            }
                        }
                    }
                }
                return false;
            case '<>value':
                if (!empty($values)) {
                    $key1 = '_all';
                    $key2 = $rule['name'];
                    foreach ($values as $input) {
                        foreach ((array)$rule['value'] as $rule_value) {
                            if (!strpos($rule_value, ',')
                                || (!$rule_value = explode(',', $rule_value))
                                || count($rule_value) < 2
                            ) continue;

                            if ($input[$key1][$key2] >= $rule_value[0]
                                && $input[$key1][$key2] <= $rule_value[1]
                            ) return true;
                        }
                    }
                }
                return false;
            case 'empty':
                return empty($values) === $rule['value'];
            case 'filled':
                return !empty($values) === $rule['value'];
            default:
                return false;
        }
    }
}