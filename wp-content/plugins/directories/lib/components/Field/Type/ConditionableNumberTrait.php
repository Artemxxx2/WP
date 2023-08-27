<?php
namespace SabaiApps\Directories\Component\Field\Type;

use SabaiApps\Directories\Component\Field\IField;
use SabaiApps\Directories\Component\Entity\Type\IEntity;

trait ConditionableNumberTrait
{
    public function fieldConditionableInfo(IField $field, $isServerSide = false)
    {
        return [
            '' => [
                'compare' => ['value', '!value', '<value', '>value', '<>value', 'empty', 'filled'],
                'tip' => __('Enter a single numeric value or two numeric values separated with a comma', 'directories'),
                'example' => 7,
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
                return is_numeric($value) ? ['type' => $compare, 'value' => $value] : null;
            case '<>value':
                if (!strpos($value, ',')
                    || (!$value= explode(',', $value))
                    || !is_numeric($value[0])
                    || !is_numeric($value[1])
                ) return;

                return ['type' => $compare, 'value' => $value[0] . ',' . $value[1]];
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
                        $input = $input[$this->_valueColumn];
                    }
                    if ($input == $rule['value']) {
                        if ($rule['type'] === '!value') return false;
                    } else {
                        if ($rule['type'] === 'value') return false;
                    }
                }
                // All rules matched or did not match.
                return true;
            case '<value':
            case '>value':
                if (empty($values)) return false;

                foreach ($values as $input) {
                    if (is_array($input)) {
                        $input = $input[$this->_valueColumn];
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
                    || (!$rule_value= explode(',', $rule['value']))
                    || !is_numeric($rule_value[0])
                    || !is_numeric($rule_value[1])
                ) return;

                foreach ($values as $input) {
                    if (is_array($input)) {
                        $input = $input[$this->_valueColumn];
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
