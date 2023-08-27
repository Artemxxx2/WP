<?php
namespace SabaiApps\Directories\Component\Field;

interface IField
{
    public function getFieldId();
    public function getFieldType();
    public function getFieldName();
    public function getFieldLabel($translate = false);
    public function getFieldDescription($translate = false);
    public function getFieldSettings();
    public function getFieldDefaultValue();
    public function getFieldWidget();
    public function getFieldWidgetSettings();
    public function isFieldRequired();
    public function getFieldMaxNumItems();
    public function getFieldConditions();
    public function getFieldData($name);
    public function setFieldData($name, $value);
    public function isPropertyField();
    public function isCustomField();
    public function getBundleName();
    public function __toString();
}
