<?php
namespace SabaiApps\Directories\Component\Field\Widget;

use SabaiApps\Directories\Component\Field\IField;
use SabaiApps\Directories\Component\Entity\Model\Bundle;
use SabaiApps\Directories\Component\Entity\Type\IEntity;

class RadioButtonsWidget extends AbstractWidget
{
    protected $_options = [];

    protected function _fieldWidgetInfo()
    {
        return [
            'label' => __('Radio buttons', 'directories'),
            'field_types' => ['choice'],
            'default_settings' => [
                'columns' => 3,
                'sort' => false,
            ],
        ];
    }
    
    public function fieldWidgetSettingsForm($fieldType, Bundle $bundle, array $settings, array $parents = [], array $rootParents = [])
    {
        return [
            'columns'  => [
                '#type' => 'select',
                '#title' => __('Number of columns', 'directories'),
                '#options' => [1 => 1, 2 => 2, 3 => 3, 4 => 4, 6 => 6, 12 => 12],
                '#default_value' => $settings['columns'],
                '#weight' => 1
            ],
            'sort' => [
                '#title' => __('Sort by label', 'directories'),
                '#type' => 'checkbox',
                '#default_value' => !empty($settings['sort']),
                '#weight' => 5,
            ],
        ];
    }

    public function fieldWidgetForm(IField $field, array $settings, $value = null, IEntity $entity = null, array $parents = [], $language = null)
    {
        $form = [
            '#type' => 'radios',
            '#options' => $this->_getOptions($field, $settings, $entity, $language),
            '#default_value' => ($default_value = $this->_getDefaultOptions($field, $settings, $value, $entity, $language)) ? $default_value : null,
            '#columns' => $settings['columns'],
        ];
        if ($icons = $this->_getOptionIcons($field, $settings, $entity, $language)) {
            $form['#option_no_escape'] = true;
            foreach (array_keys($form['#options']) as $value) {
                $form['#options'][$value] = $this->_application->H($form['#options'][$value]);
                if (!empty($icons[$value])) {
                    $form['#options'][$value] = '<i class="fa-fw ' . $icons[$value] . '"></i> ' . $form['#options'][$value];
                }
            }
        }
        return $form;
    }

    protected function _getOptions(IField $field, array $settings, IEntity $entity = null, $language = null)
    {
        $options = $this->_loadOptions($field, $settings, $entity, $language);
        return empty($options['options']) ? [] : $options['options'];
    }

    protected function _getOptionIcons(IField $field, array $settings, IEntity $entity = null, $language = null)
    {
        $options = $this->_loadOptions($field, $settings, $entity, $language);
        return empty($options['icons']) ? [] : $options['icons'];
    }

    protected function _getDefaultOptions(IField $field, array $settings, $value = null, IEntity $entity = null, $language = null)
    {
        if (isset($value)) return is_array($value) ? array_values($value) : $value;

        $options = $this->_loadOptions($field, $settings, $entity, $language);
        return empty($options['default']) ? [] : $options['default'];
    }

    protected function _loadOptions(IField $field, array $settings, IEntity $entity = null, $language = null)
    {
        if (!isset($this->_options[$field->getFieldId()])) {
            $this->_options[$field->getFieldId()] = $this->_application->Field_ChoiceOptions($field, !empty($settings['sort']), $language);
        }
        return $this->_options[$field->getFieldId()];
    }
}