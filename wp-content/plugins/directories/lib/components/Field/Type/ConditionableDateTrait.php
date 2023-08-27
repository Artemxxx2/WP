<?php

namespace SabaiApps\Directories\Component\Field\Type;

use SabaiApps\Directories\Component\Field\IField;
use SabaiApps\Directories\Component\Entity\Type\IEntity;

trait ConditionableDateTrait
{
    public function fieldConditionableInfo(IField $field, $isServerSide = false)
    {
        if (!$isServerSide) return;

        return [
            '' => [
                'compare' => ['value', '!value', '<value', '>value', '<>value', 'empty', 'filled'],
                'tip' => __('Enter a single date string for exact date match, two date strings separated with a comma for date range search.', 'directories'),
                'example' => '2020/3/28,today',
            ],
        ];
    }

    public function fieldConditionableRule(IField $field, $compare, $value = null, $name = '')
    {
        switch ($compare) {
            case 'value':
            case '!value':
            case '<value':
            case '>value':
                if (!$num = strtotime($value)) return;

                return [
                    'type' => $compare,
                    'value' => $this->_application->getPlatform()->getSiteToSystemTime($num),
                ];
            case '<>value':
                if (!strpos($value, ',')
                    || (!$value = explode(',', $value))
                    || !isset($value[0])
                    || !isset($value[1])
                    || (!$min = strtotime($value[0]))
                    || (!$max = strtotime($value[1]))
                    || $min > $max
                ) return;

                return [
                    'type' => $compare,
                    'value' => $this->_application->getPlatform()->getSiteToSystemTime($min) . ',' . $this->_application->getPlatform()->getSiteToSystemTime($max),
                ];
            case 'empty':
                return ['type' => 'filled', 'value' => false];
            case 'filled':
                return ['type' => 'empty', 'value' => false];
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

                foreach ($values as $input) {
                    if (is_array($input)) {
                        $input = $input['value'];
                    }
                    if ($input == $rule['value']) {
                        if ($rule['type'] === '!value') return false;
                    } else {
                        if ($rule['type'] === 'value') return false;
                    }
                }
                return true;
            case '<value':
            case '>value':
                if (empty($values)) return false;

                foreach ($values as $input) {
                    if (is_array($input)) {
                        $input = $input['value'];
                    }
                    if ($input < $rule['value']) {
                        if ($rule['type'] === '<value') return true;
                    } elseif ($input > $rule['value']) {
                        if ($rule['type'] === '>value') return true;
                    }
                }
                return false;
            case '<>value':
                if (empty($values)) return false;

                if (!strpos($rule['value'], ',')
                    || (!$rule_value = explode(',', $rule['value']))
                    || !is_numeric($rule_value[0])
                    || !is_numeric($rule_value[1])
                ) return;

                foreach ($values as $input) {
                    if (is_array($input)) {
                        $input = $input['value'];
                    }

                    if ($input >= $rule_value[0] && $input <= $rule_value[1]) return true;
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
