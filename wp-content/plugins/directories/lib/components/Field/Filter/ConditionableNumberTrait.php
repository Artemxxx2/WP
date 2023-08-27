<?php
namespace SabaiApps\Directories\Component\Field\Filter;

use SabaiApps\Directories\Component\Field\IField;

trait ConditionableNumberTrait
{
    public function fieldFilterConditionableInfo(IField $field)
    {
        return [
            '' => [
                'compare' => ['value', '!value', '<value', '>value', '<>value'],
                'tip' => __('Enter a single numeric value or two numeric values separated with a comma', 'directories'),
                'example' => 3,
            ],
        ];
    }

    public function fieldFilterConditionableRule(IField $field, $filterName, array $settings, $compare, $value = null, $name = '')
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
            default:
        }
    }
}