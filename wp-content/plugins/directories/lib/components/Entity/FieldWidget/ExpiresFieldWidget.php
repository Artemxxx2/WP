<?php
namespace SabaiApps\Directories\Component\Entity\FieldWidget;

use SabaiApps\Directories\Component\Entity\Type\IEntity;
use SabaiApps\Directories\Component\Field;
use SabaiApps\Directories\Component\Form;

class ExpiresFieldWidget extends Field\Widget\AbstractWidget
{
    protected function _fieldWidgetInfo()
    {
        return [
            'field_types' => [$this->_name],
        ];
    }

    public function fieldWidgetForm(Field\IField $field, array $settings, $value = null, IEntity $entity = null, array $parents = [], $language = null)
    {
        if (empty($field->Bundle->info['entity_expire'])
            || !$this->_application->HasPermission('directory_admin_directory_' . $field->Bundle->group)
        ) return;

        $form = [
            '#type' => 'fieldset',
            '#element_validate' => array(array($this, '_fieldWidgetSubmitCallback')),
            'value' => [
                '#type' => 'datepicker',
                '#default_value' => $value,
                '#empty_value' => 0,
                '#disable_time' => true,
                '#weight' => 1,
            ],
        ];

        if ($this->_application->isComponentLoaded('Payment')
            && !empty($field->Bundle->info['payment_enable'])
        ) {
            $form['warning'] = [
                '#type' => 'item',
                '#markup' => '<div class="' . DRTS_BS_PREFIX . 'alert ' . DRTS_BS_PREFIX . 'alert-warning">'
                    . $this->_application->H(__('This setting is ignored since a payment plan is assigned.', 'directories'))
                    . '</div>',
                '#weight' => 0,
                '#states' => [
                    'invisible' => [
                        '[name="' . $this->_application->Form_FieldName(array_merge(array_slice($parents, 0, -2), ['payment_plan', 0, 'plan_id'])) . '"]' => ['value' => 0],
                    ],
                ],
            ];
        }
        
        return $form;
    }
    
    public function _fieldWidgetSubmitCallback(Form\Form $form, &$value, $element)
    {
        if (empty($value['value'])) $value = null;
    }
}