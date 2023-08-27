<?php
namespace SabaiApps\Directories\Component\Field\Widget;

use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Field\IField;

class UrlWidget extends TextfieldWidget
{
    protected function _fieldWidgetInfo()
    {
        $info = parent::_fieldWidgetInfo();
        $info['field_types'] = [$this->_name, 'user_url'];
        $info['default_settings']['autopopulate'] = false;
        return $info;
    }

    public function fieldWidgetSettingsForm($fieldType, Entity\Model\Bundle $bundle, array $settings, array $parents = [], array $rootParents = [])
    {
        $form = parent::fieldWidgetSettingsForm($fieldType, $bundle, $settings, $parents, $rootParents);
        if ($fieldType instanceof IField
            && $fieldType->getFieldType() !== 'user_url'
        ) {
            $form['autopopulate'] = array(
                '#type' => 'checkbox',
                '#title' => __("Auto-populate field with the current user's website URL", 'directories'),
                '#default_value' => $settings['autopopulate'],
            );
        }
        return $form;
    }

    public function fieldWidgetForm(IField $field, array $settings, $value = null, Entity\Type\IEntity $entity = null, array $parents = [], $language = null)
    {
        $form = parent::fieldWidgetForm($field, $settings, $value, $entity, $parents, $language);
        if (!empty($settings['autopopulate'])
            || $field->getFieldType() === 'user_url'
        ) {
            $form['#autopopulate'] = 'url';
        }

        return $form;
    }
}