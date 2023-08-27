<?php
namespace SabaiApps\Directories\Component\Field\Widget;

use SabaiApps\Directories\Component\Field\IField;
use SabaiApps\Directories\Component\Entity\Model\Bundle;
use SabaiApps\Directories\Component\Entity\Type\IEntity;

class CheckboxesWidget extends RadioButtonsWidget
{
    protected function _fieldWidgetInfo()
    {
        $info = parent::_fieldWidgetInfo();
        $info['label'] = __('Checkboxes', 'directories');
        $info['accept_multiple'] = true;
        $info['default_settings'] += [
            'popup' => false,
            'height' => 0,
        ];
        return $info;
    }

    public function fieldWidgetSettingsForm($fieldType, Bundle $bundle, array $settings, array $parents = [], array $rootParents = [])
    {
        return parent::fieldWidgetSettingsForm($fieldType, $bundle, $settings, $parents, $rootParents) + [
            'popup' => [
                '#title' => __('Show option list in popup', 'directories'),
                '#type' => 'checkbox',
                '#default_value' => !empty($settings['popup']),
                '#weight' => 2,
            ],
            'height' => [
                '#title' => __('Option list height', 'directories'),
                '#type' => 'slider',
                '#min_value' => 0,
                '#max_value' => 500,
                '#min_text' => __('Auto', 'directories'),
                '#field_suffix' => 'px',
                '#integer' => true,
                '#default_value' => $settings['height'],
                '#states' => [
                    'visible' => [
                        sprintf('[name="%s"]', $this->_application->Form_FieldName(array_merge($parents, ['columns']))) => ['value' => 1],
                        sprintf('[name="%s"]', $this->_application->Form_FieldName(array_merge($parents, ['popup']))) => ['type' => 'checked', 'value' => 1],
                    ],
                ],
                '#weight' => 3,
            ],
        ];
    }

    public function fieldWidgetForm(IField $field, array $settings, $value = null, IEntity $entity = null, array $parents = [], $language = null)
    {
        $form = parent::fieldWidgetForm($field, $settings, $value, $entity, $parents, $language);
        $form['#type'] = 'checkboxes';
        $form['#max_selection'] = $field->getFieldMaxNumItems();
        if (!empty($settings['popup'])) {
            $form['#type'] = 'select';
            $form['#multiple'] = true;
            $form['#multiselect'] = true;
            $form['#multiselect_height'] = !empty($settings['height']) && $settings['columns'] == 1 ? $settings['height'] : 0;
            $form['#placeholder'] = sprintf(__('Select %s', 'directories'), $field->getFieldLabel());
        }
        
        return $form;
    }

    protected function _getOptionIcons(IField $field, array $settings, IEntity $entity = null, $language = null)
    {
        if (!empty($settings['popup'])) return []; // icons can't be used for popup list

        return parent::_getOptionIcons($field, $settings, $entity, $language);
    }
}