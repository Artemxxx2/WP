<?php
namespace SabaiApps\Directories\Component\Payment\Feature;

use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Payment\Model\Feature;

class FieldsFeature extends AbstractFeature implements IAddonFeature
{    
    protected function _paymentFeatureInfo()
    {
        return array(
            'label' => __('Field Settings', 'directories-payments'),
            'weight' => 3,
            'default_settings' => array(
                'all' => true,
                'fields' => null,
                'fields_disabled' => [],
            ),
        );
    }
    
    public function paymentFeatureSettingsForm(Entity\Model\Bundle $bundle, array $settings, $planType = 'base', array $parents = [])
    {
        if (!$options = $this->_getFieldOptions($bundle)) return;
        
        if (empty($settings['fields_disabled'])) {
            $values = array_keys($options);
        } else {
            $values = $options;
            foreach ($settings['fields_disabled'] as $field_name) {
                unset($values[$field_name]);
            }
            $values = array_keys($values);
        }
        
        return array( 
            '#element_validate' => array(
                array(array($this, 'submitSettings'), array($options))
            ),
            'all' => array(
                '#title' => __('Allowed fields', 'directories-payments'),
                '#on_label' => __('All fields', 'directories-payments'),
                '#type' => 'checkbox',
                '#switch' => false,
                '#default_value' => !empty($settings['all']),
                '#horizontal' => true,
            ),
            'fields' => array(
                '#type' => 'checkboxes',
                '#options' => $options,
                '#default_value' => $values,
                '#horizontal' => true,
                '#columns' => 2,
                '#states' => array(
                    'invisible' => array(
                        sprintf('input[name="%s[all][]"]', $this->_application->Form_FieldName($parents)) => array('type' => 'checked', 'value' => true),
                    ),                       
                ),
            ),
        );
    }

    protected function _getFieldOptions(Entity\Model\Bundle $bundle)
    {
        $fields = $options = [];
        foreach ($this->_application->Entity_Field($bundle->name) as $field_name => $field) {
            if (!$field->getFieldWidget()
                || $field->getFieldData('_no_ui')
                || $field->getFieldData('disabled')
                || (!$field_type = $this->_application->Field_Type($field->getFieldType(), true))
                || $field_type->fieldTypeInfo('admin_only')
                || (!$field->isCustomField() && !$field_type->fieldTypeInfo('disablable'))
            ) continue;

            $weight = $field->getFieldData('weight');
            if (!isset($fields[$weight])) $fields[$weight] = [];
            $fields[$weight][$field_name] = sprintf(__('%s (%s)', 'directories-payments'), $field, $field_type->fieldTypeInfo('label'));
        }
        ksort($fields);
        foreach ($fields as $_fields) {
            $options += $_fields;
        }

        return $options;
    }
    
    public function submitSettings($form, &$value, $element, $options)
    {
        $value['fields_disabled'] = [];
        if (empty($value['all'])) {
            foreach (array_keys($options) as $field_name) {
                if (!in_array($field_name, $value['fields'])) {
                    $value['fields_disabled'][] = $field_name;
                }
            }
        }
    }

    public function paymentFeatureOnEntityForm(Entity\Model\Bundle $bundle, array $settings, array &$form, Entity\Type\IEntity $entity = null, $isAdmin = false)
    {
        if ($isAdmin && $this->_application->IsAdministrator()) return; // do not restrict for administrators
        
        if (!empty($settings[0]['all']) || empty($settings[0]['fields_disabled'])) return;

        if (!empty($settings[1]['fields'])) {
            $settings[0]['fields_disabled'] = array_diff($settings[0]['fields_disabled'], $settings[1]['fields']);
        }
        
        foreach (array_keys($form) as $field_name) {
            if (in_array($field_name, $settings[0]['fields_disabled'])) {
                unset($form[$field_name]);
            }
        }
    }

    public function paymentAddonFeatureSupports(Entity\Model\Bundle $bundle)
    {
        return true;
    }

    public function paymentAddonFeatureSettingsForm(Entity\Model\Bundle $bundle, array $settings, array $parents = [])
    {
        return $this->_getAddonSettingsForm($bundle, $settings, $parents);
    }

    protected function _getAddonSettingsForm(Entity\Model\Bundle $bundle, array $settings, array $parents = [], $horizontal = true)
    {
        if (!$options = $this->_getFieldOptions($bundle)) return;

        return [
            'fields' => [
                '#title' => __('Available fields', 'directories-payments'),
                '#type' => 'checkboxes',
                '#options' => $options,
                '#default_value' => isset($settings['fields']) ? $settings['fields'] : null,
                '#horizontal' => $horizontal,
                '#columns' => 2,
            ],
        ];
    }

    public function paymentAddonFeatureExtraSettingsForm(Entity\Model\Bundle $bundle, array $planFeatures, array $currentExtraFeatures, array $parents = [])
    {
        $form = $this->_getAddonSettingsForm($bundle, $currentExtraFeatures, $parents, false);
        $form['fields']['#title'] = __('Additional fields', 'directories-payments');
        if (!empty($planFeatures[$this->_name]['fields'])) {
            $form['fields']['#options'] = array_diff_key($form['fields']['#options'], array_flip($planFeatures[$this->_name]['fields']));
        }
        return $form;
    }

    public function paymentAddonFeatureIsEnabled(Entity\Model\Bundle $bundle, array $settings)
    {
        return !empty($settings['fields']);
    }

    public function paymentAddonFeatureIsOrderable(array $settings, array $currentFeatures)
    {
        return empty($currentFeatures[$this->_name]['all'])
            && !empty($currentFeatures[$this->_name]['fields_disabled'])
            && !empty($settings['fields'])
            && array_intersect($currentFeatures[$this->_name]['fields_disabled'], $settings['fields']);
    }

    public function paymentAddonFeatureExtraIsOrderable(array $planFeatures)
    {
        return empty($planFeatures[$this->_name]['all'])
            && !empty($planFeatures[$this->_name]['fields_disabled']);
        return false;
    }

    public function paymentFeatureApply(Entity\Type\IEntity $entity, Feature $feature, array &$values, $isAddon = false)
    {
        if (!$isAddon) return;

        return $this->_applyAddonFeature($entity, $feature, $values, true);
    }

    public function paymentFeatureUnapply(Entity\Type\IEntity $entity, Feature $feature, array &$values, $isAddon = false)
    {
        if (!$isAddon) return;

        return $this->_unapplyAddonFeature($entity, $feature, $values, true);
    }
}