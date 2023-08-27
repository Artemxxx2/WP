<?php
namespace SabaiApps\Directories\Component\Field\Type;

use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Field\IField;

abstract class AbstractValueType extends AbstractType implements ICopiable
{
    protected $_valueColumn = 'value';

    public function fieldTypeOnSave(IField $field, array $values, array $currentValues = null, array &$extraArgs = [])
    {
        $settings = (array)$field->getFieldSettings();
        $ret = [];
        foreach ($values as $value) {
            if (is_array($value)) {
                if (empty($value) || !isset($value[$this->_valueColumn])) continue;

                $_value = $this->_onSaveValue($field, (string)$value[$this->_valueColumn], $settings);
                if (is_null($_value) || strlen($_value) === 0) continue;

                $value[$this->_valueColumn] = $_value;
                $ret[] = $value;
            } else {
                $value = $this->_onSaveValue($field, $value, $settings);
                if (is_null($value) || strlen($value) === 0) continue;

                $ret[][$this->_valueColumn] = $value;
            }
        }

        return $ret;
    }

    protected function _onSaveValue(IField $field, $value, array $settings)
    {
        $value = (string)$value;
        return strlen($value) === 0 ? null : $value;
    }

    public function fieldTypeOnLoad(IField $field, array &$values, Entity\Type\IEntity $entity, array $allValues)
    {
        $settings = (array)$field->getFieldSettings();
        foreach ($values as $key => $value) {
            $values[$key] = $this->_onLoadValue($field, $value[$this->_valueColumn], $settings);
        }
    }

    protected function _onLoadValue(IField $field, $value, array $settings)
    {
        return $value;
    }
    
    public function fieldTypeIsModified(IField $field, $valueToSave, $currentLoadedValue)
    {
        $new = [];
        foreach ($valueToSave as $value) {
            $new[] = $value[$this->_valueColumn];
        }
        return $currentLoadedValue !== $new;
    }

    public function fieldCopyValues(IField $field, array $values, array &$allValue, $lang = null)
    {
        return $values;
    }
}