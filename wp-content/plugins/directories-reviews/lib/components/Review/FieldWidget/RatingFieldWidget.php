<?php
namespace SabaiApps\Directories\Component\Review\FieldWidget;

use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Field;

class RatingFieldWidget extends Field\Widget\AbstractWidget
{
    protected function _fieldWidgetInfo()
    {
        return [
            'label' => __('Rating Stars', 'directories-reviews'),
            'field_types' => [$this->_name],
            'default_settings' => ['criteria' => [], 'step' => '0.5', 'no_rating_txt' => __('No rating', 'directories-reviews')],
            'accept_multiple' => true,
            'disable_edit_max_num_items' => true,
        ];
    }
    
    public function fieldWidgetSettingsForm($fieldType, Entity\Model\Bundle $bundle, array $settings, array $parents = [], array $rootParents = [])
    {
        return [
            'step' => [
                '#type' => 'select',
                '#title' => __('Rating step', 'directories-reviews'),
                '#default_value' => $settings['step'],
                '#options' => [
                    '0.5' => '0.5',
                    '1' => '1.0'
                ],
                '#option_no_escape' => true,
            ],
            'no_rating_txt' => [
                '#type' => 'textfield',
                '#title' => __('"No rating" text', 'directories-reviews'),
                '#default_value' => $settings['no_rating_txt'],
            ],
        ];
    }
    
    public function fieldWidgetForm(Field\IField $field, array $settings, $value = null, Entity\Type\IEntity $entity = null, array $parents = [], $language = null)
    {
        $form = [];
        foreach ($this->_application->Review_Criteria($field->Bundle, false, true) as $slug => $label) {
            if (isset($value[0][$slug]['value'])) {
                $_value = $value[0][$slug]['value'];
            } else {
                //if (isset($value[0]['_all']['value'])) { // use value from overall rating
                //    $_value = $value[0]['_all']['value'];
                //} else {
                $_value = null;
                //}
            }
            if (isset($_value)) {
                $_value = $settings['step'] == 1 ? round($_value) : $_value;
            }
            $form[$slug] = [
                '#type' => 'slider',
                '#title' => $label,
                '#min_text' => $settings['no_rating_txt'],
                '#min_value' => $settings['step'] * -1,
                '#max_value' => 5,
                '#slider_values' => range($settings['step'] * -1, 5, $settings['step']),
                '#step' => $settings['step'],
                '#default_value' => $_value,
            ];
        }
        
        // Hide rating option label if single criteria
        if (count($form) === 1) {
            unset($form[$slug]['#title']);
        }
        
        return $form;
    }
}