<?php
namespace SabaiApps\Directories\Component\Claiming\FieldWidget;

use SabaiApps\Directories\Component\Field\Widget\AbstractWidget;
use SabaiApps\Directories\Component\Field\IField;
use SabaiApps\Directories\Component\Entity\Type\IEntity;

class StatusFieldWidget extends AbstractWidget
{
    protected function _fieldWidgetInfo()
    {
        return [
            'label' => __('Claim Status', 'directories-pro'),
            'field_types' => [$this->_name],
        ];
    }

    public function fieldWidgetForm(IField $field, array $settings, $value = null, IEntity $entity = null, array $parents = [], $language = null)
    {
        if (isset($entity)
            && !$entity->isPublished()
        ) {
            // Need to render hidden field for IConditionable to work properly
            return [
                '#type' => 'item',
                '#markup' => $this->_getLabel(__('Pending', 'directories-pro'), 'warning')
                    . '<input type="hidden" value="" name="' . $this->_application->Form_FieldName($parents) . '" />',
            ];
        }

        if (!$this->_application->HasPermission('entity_delete_others_' . $field->Bundle->name)) return;

        $statuses = $this->_application->Claiming_Statuses();
        if ($value = isset($value) && isset($statuses[$value]) ? $value : null) { // do not allow change if approved/rejected
            $markup = $this->_getLabel($statuses[$value]['label'], $statuses[$value]['color']);
            // Need to render hidden field for IConditionable to work properly
            $markup .= '<input type="hidden" value="' . $value . '" name="' . $this->_application->Form_FieldName($parents) . '" />';
            return [
                '#type' => 'item',
                '#markup' => $markup,
            ];
        }

        $options = [];
        foreach ($statuses as $status => $status_info) {
            $options[$status] = $this->_getLabel($status_info['label'], $status_info['color']);
        }
            
        return [
            '#type' => 'radios',
            '#options' => $options,
            '#option_no_escape' => true,
            '#value' => $value,
            '#disabled' => isset($value),
        ];
    }

    protected function _getLabel($label, $color)
    {
        return sprintf(
            '<span class="%1$sbadge %1$sbadge-%2$s">%3$s</span>',
            DRTS_BS_PREFIX,
            $color,
            $this->_application->H($label)
        );
    }
}
