<?php
namespace SabaiApps\Directories\Component\Field\Filter;

use SabaiApps\Directories\Component\Field\IField;
use SabaiApps\Directories\Component\Field\Query;

class OptionFilter extends AbstractOptionFilter implements IConditionable
{
    protected $_fieldTypes = ['choice'], $_prefixLabel = false, $_labelSortable = true;
    
    protected function _getOptions(IField $field, array $settings, &$noEscape = false)
    {
        if (!$options = $this->_application->Field_ChoiceOptions($field, false)) return; // no options

        if (!empty($options['icons'])) {
            $noEscape = true;
            $ret = [];
            foreach (array_keys($options['options']) as $value) {
                if (isset($options['icons'][$value])) {
                    $style = isset($options['colors'][$value]) ? 'color:#fff;background-color:' . $this->_application->H($options['colors'][$value]) : '';
                    $ret[$value] = '<i class="drts-icon drts-icon-sm fa-fw ' . $options['icons'][$value] . '" style="' . $style . '"></i> ' . $this->_application->H($options['options'][$value]);
                } else {
                    $ret[$value] = $this->_application->H($options['options'][$value]);
                }
            }
        } else {
            $ret = $options['options'];
        }
        return $ret;
    }
    
    public function fieldFilterDoFilter(Query $query, IField $field, array $settings, $value, array &$sorts)
    {
        $value = (array)$value;
        if (count($value) === 1) {
            $query->fieldIs($field, array_shift($value), $this->_valueColumn);
        } elseif ($settings['andor'] === 'OR' || !$this->_isMultipleChoiceField($field)) { // AND query does not make sense for non-multiple choice fields
            $query->fieldIsIn($field, $value, $this->_valueColumn);
        } else {
            $query->startCriteriaGroup('AND')->fieldIs($field, array_shift($value), $this->_valueColumn);
            $i = 1;
            foreach ($value as $_value) {
                $query->fieldIs($field, $_value, $this->_valueColumn, $field->getFieldName() . ++$i);
            }
            $query->finishCriteriaGroup();
        }
    }
    
    protected function _isMultipleChoiceField(IField $field)
    {
        return $field->getFieldWidget() === 'checkboxes'
            || ($field->getFieldWidget() === 'select' && $field->getFieldMaxNumItems() !== 1);
    }

    public function fieldFilterConditionableInfo(IField $field)
    {
        return [
            '' => [
                'compare' => ['value', '!value', 'one', 'empty', 'filled'],
                'tip' => __('Enter values separated with commas.', 'directories'),
                'example' => $this->_getFieldEntryExample($field),
            ],
        ];
    }

    public function fieldFilterConditionableRule(IField $field, $filterName, array $settings, $compare, $value = null, $name = '')
    {
        switch ($compare) {
            case 'value':
            case '!value':
            case 'one':
                $value = trim($value);
                if (strpos($value, ',')) {
                    if (!$value = explode(',', $value)) return;

                    $value = array_map('trim', $value);
                }
                return ['type' => $compare, 'value' => $value];
            case 'empty':
                return ['type' => 'filled', 'value' => false];
            case 'filled':
                return ['type' => 'empty', 'value' => false];
            default:
                return;
        }
    }

    protected function _getFieldEntryExample(IField $field)
    {
        $settings = $field->getFieldSettings();
        if (!empty($settings['options']['options'])) {
            return implode(',', array_slice(array_keys($settings['options']['options']), 0, 4));
        }
        return 'aaa,bb,cccc';
    }
}
