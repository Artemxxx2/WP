<?php
namespace SabaiApps\Directories\Component\Field\Widget;

use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Field\IField;

class TextfieldWidget extends AbstractWidget
{
    protected function _fieldWidgetInfo()
    {
        return array(
            'label' => __('Text input field', 'directories'),
            'field_types' => array('string', 'number'),
            'default_settings' => array(
                'autopopulate' => '',
                'placeholder' => null,
                'mask' => null,
            ),
            'repeatable' => true,
        );
    }

    public function fieldWidgetSettingsForm($fieldType, Entity\Model\Bundle $bundle, array $settings, array $parents = [], array $rootParents = [])
    {
        $form = [];
        $field_type = $fieldType instanceof IField ? $fieldType->getFieldType() : $fieldType;
        if ($field_type === 'string') {
            $form += array(
                'autopopulate' => array(
                    '#type' => 'select',
                    '#title' => __('Auto-populate field', 'directories'),
                    '#options' => array(
                        '' => __('Do not auto-populate', 'directories'),
                        'email' => __('E-mail address of current user', 'directories'),
                        'url' => __('Website URL of current user', 'directories'),
                        'username' => __('User name of current user', 'directories'),
                        'name' => __('Display name of current user', 'directories'),
                    ),
                    '#default_value' => $settings['autopopulate'],
                ),
            );
        }
        if ($field_type !== 'number') {
            $form['placeholder'] = array(
                '#type' => 'textfield',
                '#title' => __('Placeholder', 'directories'),
                '#default_value' => $settings['placeholder'],
            );
            $form['mask'] = array(
                '#type' => 'textfield',
                '#title' => __('Input mask', 'directories'),
                '#description' => __('Use "a" to mask letter inputs (A-Z,a-z), "9" for numbers (0-9) and "*" for both.', 'directories'),
                '#default_value' => $settings['mask'],
                '#placeholder' => '(999) 999-9999',
            );
        }
        
        return $form;
    }

    public function fieldWidgetForm(IField $field, array $settings, $value = null, Entity\Type\IEntity $entity = null, array $parents = [], $language = null)
    {
        $field_settings = $field->getFieldSettings();
        $form = [
            '#type' => $field->getFieldType(),
            '#default_value' => isset($value) ? $value : null,
            '#field_prefix' => isset($field_settings['prefix']) && strlen($field_settings['prefix'])
                ? $this->_application->System_TranslateString($field_settings['prefix'], $field->getFieldName() . '_field_prefix', 'entity_field')
                : null,
            '#field_suffix' => isset($field_settings['suffix']) && strlen($field_settings['suffix'])
                ? $this->_application->System_TranslateString($field_settings['suffix'], $field->getFieldName() . '_field_suffix', 'entity_field')
                : null,
        ];
        switch ($field->getFieldType()) {
            case 'number':
                if ($field_settings['decimals'] > 0) {
                    $form['#numeric'] = true;
                    $form['#min_value'] = isset($field_settings['min']) && is_numeric($field_settings['min']) ? $field_settings['min'] : null;
                    $form['#max_value'] = isset($field_settings['max']) && is_numeric($field_settings['max']) ? $field_settings['max'] : null;
                    $form['#step'] = $field_settings['decimals'] == 1 ? 0.1 : 0.01;
                } else {
                    $form['#integer'] = true;
                    $form['#min_value'] = isset($field_settings['min']) ? intval($field_settings['min']) : null;
                    $form['#max_value'] = isset($field_settings['max']) ? intval($field_settings['max']) : null;
                }
                if (!isset($form['#size'])) {
                    $form['#size'] = 20;
                }
                break;
            default:
                $form['#min_length'] = isset($field_settings['min_length']) ? $field_settings['min_length'] : null;
                $form['#max_length'] = isset($field_settings['max_length']) ? $field_settings['max_length'] : null;
                $form['#char_validation'] = isset($field_settings['char_validation']) ? $field_settings['char_validation'] : 'none';
                switch ($form['#char_validation']) {
                    case 'email':
                        $form['#type'] = 'email';
                        break;
                    case 'url':
                        $form['#type'] = 'url';
                        break;
                    case 'regex':
                        $form['#regex'] = isset($field_settings['regex']) ? $field_settings['regex'] : null;
                    case 'integer':
                        $form['#integer_allow_string'] = true;
                    default:
                        $form['#type'] = 'textfield';
                }
                $form['#placeholder'] = $settings['placeholder'];
                $form['#mask'] = $settings['mask'];
                $form['#autopopulate'] = !empty($settings['autopopulate']) ? $settings['autopopulate'] : null;
        }

        return $form;
    }
}