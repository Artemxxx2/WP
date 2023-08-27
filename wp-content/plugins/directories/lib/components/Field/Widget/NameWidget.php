<?php
namespace SabaiApps\Directories\Component\Field\Widget;

use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Field\IField;

class NameWidget extends AbstractWidget
{
    protected function _fieldWidgetInfo()
    {
        return [
            'field_types' => [$this->_name],
            'default_settings' => [
                'horizontal' => true,
                'first_name_weight' => 1,
                'middle_name_weight' => 2,
                'last_name_weight' => 3,
            ],
            'repeatable' => true,
            'requirable' => false,
            'display_required' => true,
        ];
    }

    public function fieldWidgetSettingsForm($fieldType, Entity\Model\Bundle $bundle, array $settings, array $parents = [], array $rootParents = [])
    {
        return [
            'horizontal' => [
                '#title' => __('Show horizontally', 'directories'),
                '#type' => 'checkbox',
                '#default_value' => !empty($settings['horizontal']),
            ],
        ];
    }

    public function fieldWidgetForm(IField $field, array $settings, $value = null, Entity\Type\IEntity $entity = null, array $parents = [], $language = null)
    {
        $form = [
            '#type' => 'fieldset',
            '#default_value' => isset($value) ? $value : null,
        ];
        $field_settings = $field->getFieldSettings();
        $name_fields = [];
        if (!empty($field_settings['middle_name_field'])) {
            $form['middle_name'] = [
                '#type' => 'textfield',
                '#title' => __('Middle name', 'directories'),
                '#weight' => $settings['middle_name_weight'],
                '#horizontal' => empty($settings['horizontal']),
                '#required' => $field->isFieldRequired() && empty($field_settings['first_name_field']) && empty($field_settings['last_name_field']),
                '#default_value' => isset($value['middle_name']) ? $value['middle_name'] : null,
            ];
            $name_fields[] = 'middle_name';
        }
        if (!empty($field_settings['last_name_field'])) {
            $form['last_name'] = [
                '#type' => 'textfield',
                '#title' => __('Last name', 'directories'),
                '#weight' => $settings['last_name_weight'],
                '#horizontal' => empty($settings['horizontal']),
                '#required' => $field->isFieldRequired(),
                '#default_value' => isset($value['last_name']) ? $value['last_name'] : null,
            ];
            $name_fields[] = 'last_name';
        }
        if (!empty($field_settings['first_name_field'])
            || empty($name_fields) // needs at least one name field, so force if none
        ) {
            $form['first_name'] = [
                '#type' => 'textfield',
                '#title' => __('First name', 'directories'),
                '#weight' => $settings['first_name_weight'],
                '#horizontal' => empty($settings['horizontal']),
                '#required' => $field->isFieldRequired(),
                '#default_value' => isset($value['first_name']) ? $value['first_name'] : null,
            ];
            $name_fields[] = 'first_name';
        }

        // Bail if no name field
        if (empty($name_fields)) return;

        if (!empty($field_settings['prefix_field'])
            && !empty($field_settings['prefixes'])
        ) {
            $prefixes = $this->_application->Field_Type('name')->getNamePrefixOptions();
            $prefix_options = ['' => __('— Select —', 'directories')];
            foreach ($field_settings['prefixes'] as $prefix) {
                $prefix_options[$prefix] = $prefixes[$prefix];
            }
            $form['prefix'] = [
                '#type' => 'select',
                '#title' => _x('Prefix', 'name salutation', 'directories'),
                '#options' => $prefix_options,
                '#weight' => 0,
                '#horizontal' => empty($settings['horizontal']),
                '#default_value' => isset($value['prefix']) ? $value['prefix'] : null,
            ];
        }
        if (!empty($field_settings['suffix_field'])) {
            $form['suffix'] = [
                '#type' => 'textfield',
                '#title' => _x('Suffix', 'name suffix', 'directories'),
                '#weight' => 10,
                '#horizontal' => empty($settings['horizontal']),
                '#default_value' => isset($value['suffix']) ? $value['suffix'] : null,
            ];
        }

        if (!empty($settings['horizontal'])) {
            $form['#row'] = true;

            // Count columns
            $column_count = 1;
            if (isset($form['prefix'])) {
                ++$column_count;

                // Show field title as placeholder
                $form['prefix']['#options'][''] = $form['prefix']['#title'];
                unset($form['prefix']['#title']);
            }
            if (isset($form['suffix'])) {
                ++$column_count;

                // Show field title as placeholder
                $form['suffix']['#placeholder'] = $form['suffix']['#title'];
                unset($form['suffix']['#title']);
            }

            switch ($column_count) {
                case 3:
                    switch (count($name_fields)) {
                        case 3:
                            $form['prefix']['#col'] = ['xs' => 1];
                            $form['suffix']['#col'] = ['xs' => 2];
                            $name_field_cols = 3;
                            break;
                        case 2:
                            $form['prefix']['#col'] = $form['suffix']['#col'] = ['xs' => 2];
                            $name_field_cols = 4;
                            break;
                        case 1:
                        default:
                            $form['prefix']['#col'] = ['xs' => 2];
                            $form['suffix']['#col'] = ['xs' => 4];
                            $name_field_cols = 6;
                            break;
                    }
                    break;
                case 2:
                    $prefix_or_suffix = isset($form['prefix']) ? 'prefix' : 'suffix';
                    switch (count($name_fields)) {
                        case 3:
                            $form[$prefix_or_suffix]['#col'] = ['xs' => 3];
                            $name_field_cols = 3;
                            break;
                        case 2:
                            $form[$prefix_or_suffix]['#col'] = ['xs' => 2];
                            $name_field_cols = 5;
                            break;
                        case 1:
                        default:
                            $form[$prefix_or_suffix]['#col'] = ['xs' => 3];
                            $name_field_cols = 9;
                            break;
                    }
                    break;
                case 1:
                default:
                    $name_field_cols = 12 / count($name_fields);
                    break;
            }
            foreach ($name_fields as $name_field) {
                $form[$name_field]['#col'] = ['xs' => $name_field_cols];

                // Show field title as placeholder
                $form[$name_field]['#placeholder'] = $form[$name_field]['#title'];
                unset($form[$name_field]['#title']);
            }
        }
        $form['#group'] = true; // required for Add More to work correctly

        return $form;
    }
}
