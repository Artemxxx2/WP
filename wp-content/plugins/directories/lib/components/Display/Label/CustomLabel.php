<?php
namespace SabaiApps\Directories\Component\Display\Label;

use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Field\Type\ILabellable;

class CustomLabel extends AbstractLabel
{
    protected function _displayLabelInfo(Entity\Model\Bundle $bundle)
    {
        $info = [
            'label' => __('Custom label', 'directories'),
            'default_settings' => [
                'label_type' => '',
                'label_text' => '',
                'label_field' => null,
                'conditions' => [],
            ],
            'labellable' => false,
        ];
        foreach ($this->_application->Filter('entity_label_custom_label_num', range(1, 5), [$bundle]) as $num) {
            $info['multiple'][$num] = [
                'default_checked' => $num === 1,
                'label' => sprintf(__('Custom label #%d', 'directories'), $num)
            ];
        }
        return $info;
    }

    public function displayLabelSettingsForm(Entity\Model\Bundle $bundle, array $settings, array $parents = array())
    {
        $form = ['conditions' => $this->_application->Entity_Field_conditionSettingsForm(
            $bundle->name,
            empty($settings['conditions']) ? [] : $settings['conditions'],
            array_merge($parents, ['conditions']),
            true
        )
        ];
        $labellable_fields = $this->_application->Entity_Field_options($bundle, ['interface' => 'Field\Type\ILabellable', 'return_disabled' => true]);
        if (!empty($labellable_fields[0])
            || !empty($labellable_fields[1])
        ) {
            $form += [
                'label_type' => [
                    '#title' => __('Label text', 'directories'),
                    '#type' => 'select',
                    '#default_value' => $settings['label_type'],
                    '#horizontal' => true,
                    '#options' => [
                        '' => __('Custom', 'directories'),
                        'field' => __('Select field', 'directories'),
                    ],
                    '#weight' => -5,
                ],
                'label_text' => [
                    '#type' => 'textfield',
                    '#placeholder' => __('Enter custom text here', 'directories'),
                    '#default_value' => $settings['label_text'],
                    '#horizontal' => true,
                    '#weight' => -3,
                    '#states' => [
                        'visible' => [
                            sprintf('select[name="%s"]', $this->_application->Form_FieldName(array_merge($parents, ['label_type']))) => ['value' => ''],
                        ],
                    ],
                ],
                'label_field' => [
                    '#type' => 'select',
                    '#horizontal' => true,
                    '#default_value' => $settings['label_field'],
                    '#options' => $labellable_fields[0],
                    '#options_disabled' => array_keys($labellable_fields[1]),
                    '#weight' => -3,
                    '#states' => [
                        'visible' => [
                            sprintf('select[name="%s"]', $this->_application->Form_FieldName(array_merge($parents, ['label_type']))) => ['value' => 'field'],
                        ],
                    ],
                ],
            ];
        } else {
            $form += [
                'label_type' => [
                    '#type' => 'hidden',
                    '#default_value' => '',
                ],
                'label_text' => [
                    '#title' => __('Label text', 'directories'),
                    '#type' => 'textfield',
                    '#default_value' => $settings['label_text'],
                    '#horizontal' => true,
                    '#weight' => -3,
                ],
            ];
        }

        return $form;
    }

    public function displayLabelText(Entity\Model\Bundle $bundle, Entity\Type\IEntity $entity, array $settings)
    {
        if (!empty($settings['conditions'])
            && !$this->_application->Entity_Field_checkConditions($settings['conditions'], $entity)
        ) return;

        if (empty($settings['label_type'])) {
            return [
                'label' => $settings['label_text'],
                'color' => $settings['_color'],
                'translate' => true,
            ];
        }

        if ($settings['label_type'] === 'field') {
            if (empty($settings['label_field'])
                || (!$field = $this->_application->Entity_Field($bundle, $settings['label_field']))
                || (!$field_type = $this->_application->Field_Type($field->getFieldType(), true))
                || !$field_type instanceof ILabellable
                || (!$labels = $field_type->fieldLabellableLabels($field, $entity))
            ) return;

            $ret = [];
            foreach ($labels as $label) {
                $ret[] = [
                    'label' => $label,
                    'color' => $settings['_color'],
                ];
            }
            return $ret;
        }
    }
}
