<?php
namespace SabaiApps\Directories\Component\Field\Type;

use SabaiApps\Directories\Component\Field\IField;
use SabaiApps\Directories\Component\Entity\Type\IEntity;

trait ConditionableStringTrait
{
    public function fieldConditionableInfo(IField $field, $isServerSide = false)
    {
        return [
            '' => [
                'compare' => ['value', '!value', '^value', '$value', '*value', 'empty', 'filled'],
            ],
        ];
    }
    
    public function fieldConditionableRule(IField $field, $compare, $value = null, $name = '')
    {
        switch ($compare) {
            case 'value':
            case '!value':
            case '^value':
            case '$value':
            case '*value':
                return ['type' => $compare, 'value' => $value];
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
            case '^value':
            case '$value':
            case '*value':
                if (empty($values)) return false;

                foreach ($values as $input) {
                    foreach ((array)$rule['value'] as $rule_value) {
                        if (false === $pos = strpos($input, $rule_value)) return false;

                        if ($rule['type'] === '^value') {
                            if ($pos !== 0) return false;
                        } elseif ($rule['type'] === '$value') {
                            if (substr($input, $pos) !== $rule_value) return false;
                        }
                    }
                }
                return true;
            case 'empty':
                $values = array_filter($values, 'strlen');
                return empty($values) === $rule['value'];
            case 'filled':
                $values = array_filter($values, 'strlen');
                return !empty($values) === $rule['value'];
            default:
                return false;
        }
    }
}