<?php
namespace SabaiApps\Directories\Component\Entity\Model;

class Field extends Base\Field implements \SabaiApps\Directories\Component\Field\IField
{
    private $_data;

    public function __toString()
    {
        return (string)$this->getFieldLabel();
    }

    public function getFieldId()
    {
        return $this->id;
    }

    public function getFieldType()
    {
        return $this->FieldConfig->type;
    }

    public function getFieldName()
    {
        return $this->FieldConfig->name;
    }

    public function getFieldLabel($translate = false)
    {
        $ret = $this->getFieldData('label');
        return $translate ? $this->_model->System_TranslateString($ret, $this->bundle_name . '_' . $this->getFieldName() . '_label', 'entity_field') : $ret;
    }

    public function getFieldSettings()
    {
        return (array)$this->FieldConfig->settings;
    }

    public function isPropertyField()
    {
        return $this->FieldConfig->property;
    }

    public function getFieldDescription($translate = false)
    {
        $ret = $this->getFieldData('description');
        return $translate ? $this->_model->System_TranslateString($ret, $this->bundle_name . '_' . $this->getFieldName(). '_description', 'entity_field') : $ret;
    }

    public function isFieldRequired()
    {
        return (bool)$this->getFieldData('required');
    }

    public function getFieldMaxNumItems()
    {
        return (int)$this->getFieldData('max_num_items');
    }

    public function getFieldWidget()
    {
        return $this->getFieldData('widget');
    }

    public function getFieldWidgetSettings()
    {
        return (array)$this->getFieldData('widget_settings');
    }

    public function getFieldConditions()
    {
        return (array)$this->getFieldData('conditions');
    }

    public function getFieldDefaultValue()
    {
        return $this->getFieldData('default_value');
    }

    public function setFieldType($type)
    {
        $this->FieldConfig->type = $type;

        return $this;
    }

    public function setFieldName($name)
    {
        $this->FieldConfig->name = $name;

        return $this;
    }

    public function setFieldSettings(array $settings)
    {
        $this->FieldConfig->settings = $settings;

        return $this;
    }

    public function setFieldLabel($label, $hide_label = false)
    {
        return $this->_setFieldData('label', $label)
            ->_setFieldData('hide_label', $hide_label);
    }

    public function setFieldDescription($desc)
    {
        return $this->_setFieldData('description', $desc);
    }

    public function setFieldDefaultValue($value)
    {
        return $this->_setFieldData('default_value', $value);
    }

    public function setFieldRequired($flag)
    {
        return $this->_setFieldData('required', (bool)$flag);
    }

    public function setFieldDisabled($flag)
    {
        return $this->_setFieldData('disabled', (bool)$flag);
    }

    public function setFieldMaxNumItems($num)
    {
        return $this->_setFieldData('max_num_items', (int)$num);
    }

    public function setFieldWidget($widget)
    {
        return $this->_setFieldData('widget', $widget);
    }

    public function setFieldWeight($weight)
    {
        return $this->_setFieldData('weight', $weight);
    }

    public function setFieldWidgetSettings(array $settings)
    {
        return $this->_setFieldData('widget_settings', $settings);
    }

    public function setFieldConditions(array $settings)
    {
        return $this->_setFieldData('conditions', $settings);
    }

    public function onCommit()
    {
        parent::onCommit();
        if (isset($this->_data)) $this->data = $this->_data;
    }

    private function &_getFieldData()
    {
        if (!isset($this->_data)) {
            if (!$this->data
                || (!$this->_data = $this->data)
            ) {
                $this->_data = [];
            }
        }

        return $this->_data;
    }

    public function getFieldData($name = null)
    {
        $data = $this->_getFieldData();

        return !isset($name) ? $data : (array_key_exists($name, $data) ? $data[$name] : null);
    }

    private function _setFieldData($name, $value)
    {
        $data =& $this->_getFieldData();
        $data[$name] = $value;

        return $this->markDirty();
    }

    public function setFieldData($name, $value)
    {
        if (strpos($name, '_') !== 0) return $this;

        return $this->_setFieldData($name, $value);
    }

    public function hasFieldData($name)
    {
        $data = $this->_getFieldData();

        return array_key_exists($name, $data);
    }

    public function isCustomField()
    {
        return strpos($this->getFieldName(), 'field_') === 0;
    }

    public function toDisplayElementArray()
    {
        return array(
            'name' => 'entity_form_' . $this->getFieldType(),
            'weight' => $this->getFieldData('weight'),
            'system' => !$this->isCustomField(),
            'data' => array(
                'settings' => $this->getDisplayElementData(),
            ),
        );
    }

    public function getDisplayElementData()
    {
        return array(
            'field_name' => $this->getFieldName(),
            'settings' => $this->getFieldSettings(),
        ) + $this->getFieldData();
    }

    public function getTaxonomyBundle()
    {
        if (($bundle_name = $this->getFieldData('_bundle_name'))
            && ($bundle = $this->_model->Entity_Bundle($bundle_name))
            && !empty($bundle->info['is_taxonomy'])
        ) {
            return $bundle;
        }
    }

    public function getBundleName()
    {
        return $this->bundle_name;
    }
}

class FieldRepository extends Base\FieldRepository
{
}
