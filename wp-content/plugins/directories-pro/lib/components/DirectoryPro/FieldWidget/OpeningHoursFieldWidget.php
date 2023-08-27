<?php
namespace SabaiApps\Directories\Component\DirectoryPro\FieldWidget;

use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Field\IField;
use SabaiApps\Directories\Component\Entity\Type\IEntity;
use SabaiApps\Directories\Component\Field\Widget\TimeWidget;

class OpeningHoursFieldWidget extends TimeWidget
{
    protected function _fieldWidgetInfo()
    {
        $info = parent::_fieldWidgetInfo();
        $info['label'] = __('Opening Hours', 'directories-pro');
        $info['field_types'] = [$this->_name];
        $info['default_settings'] += [];
        return $info;
    }

    public function fieldWidgetSettingsForm($fieldType, Entity\Model\Bundle $bundle, array $settings, array $parents = [], array $rootParents = [])
    {
        $form = parent::fieldWidgetSettingsForm($fieldType, $bundle, $settings, $parents, $rootParents);
        unset($form['current_time_selected']);

        return $form;
    }

    public function fieldWidgetForm(IField $field, array $settings, $value = null, IEntity $entity = null, array $parents = [], $language = null)
    {
        $form = [
            '#current_time_selected' => false,
            '#default_value' => $value,
            '#disable_day' => false,
            '#disable_end' => false,
            '#allow_empty_day' => false,
            '#all_day_options' => [
                'closed' => _x('Closed', 'opening hours', 'directories-pro'),
                'appointment' => __('Appointment only', 'directories-pro'),
            ],
            '#enable_day_bulk' => true,
        ];

        return $form + parent::fieldWidgetForm($field, $settings, $value, $entity, $parents, $language);
    }
}