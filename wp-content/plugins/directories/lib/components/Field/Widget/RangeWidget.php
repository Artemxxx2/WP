<?php
namespace SabaiApps\Directories\Component\Field\Widget;

use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Field\IField;

class RangeWidget extends AbstractWidget
{
    protected function _fieldWidgetInfo()
    {
        return array(
            'label' => __('Range input field', 'directories'),
            'field_types' => array('range'),
            'default_settings' => array(
                'step' => 1,
            ),
            'repeatable' => true,
        );
    }

    public function fieldWidgetSettingsForm($fieldType, Entity\Model\Bundle $bundle, array $settings, array $parents = [], array $rootParents = [])
    {
        return array(
            'step' => array(
                '#type' => 'number',
                '#title' => __('Slider step', 'directories'),
                '#default_value' => $settings['step'],
                '#size' => 5,
                '#numeric' => true,
                '#element_validate' => array(array($this, 'validateStep')),
                '#min_value' => 0,
            ),
        );
    }
    
    public function validateStep($form, &$value, $element)
    {
        if (empty($value)) return;
        
        $settings = $form->values['general']['settings']['settings'];
        $min_value = !empty($settings['min']) && is_numeric($settings['min']) ? $settings['min'] : 0;
        $max_value = !empty($settings['max']) && is_numeric($settings['max']) ? $settings['max'] : 100;
        
        $range = $max_value - $min_value;
        $i = $range / $value;
        if ($i <= 0
            || $range - (floor($i) * $value) > 0
        ) {
            $form->setError(sprintf(__('The full specified value range of the slider (%s - %s) should be evenly divisible by the step', 'directories'), $min_value, $max_value), $element);
        }
    }
    
    protected function _getStep(IField $field)
    {
        $settings = $field->getFieldSettings();
        return empty($settings['decimals']) ? 1 : ($settings['decimals'] == 1 ? 0.1 : 0.01);
    }

    public function fieldWidgetForm(IField $field, array $settings, $value = null, Entity\Type\IEntity $entity = null, array $parents = [], $language = null)
    {
        $field_settings = $field->getFieldSettings();
        $min = isset($field_settings['min']) ? $field_settings['min'] : null;
        $max = isset($field_settings['max']) ? $field_settings['max'] : null;
        
        return array(
            '#type' => 'range',
            '#default_value' => $value,
            '#integer' => empty($field_settings['decimals']),
            '#min_value' => $min,
            '#max_value' => $max,
            '#step' => !empty($settings['step']) ? $settings['step'] : $this->_getStep($field),
            '#field_prefix' => isset($field_settings['prefix']) && strlen($field_settings['prefix'])
                ? $this->_application->System_TranslateString($field_settings['prefix'], $field->getFieldName() . '_field_prefix', 'entity_field')
                : null,
            '#field_suffix' => isset($field_settings['suffix']) && strlen($field_settings['suffix'])
                ? $this->_application->System_TranslateString($field_settings['suffix'], $field->getFieldName() . '_field_suffix', 'entity_field')
                : null,
        );
    }
}