<?php
namespace SabaiApps\Directories\Component\Entity\FieldWidget;

use SabaiApps\Directories\Component\Entity\Type\IEntity;
use SabaiApps\Directories\Component\Entity\FieldType\FeaturedFieldType;
use SabaiApps\Directories\Component\Field;
use SabaiApps\Directories\Component\Form;

class FeaturedFieldWidget extends Field\Widget\AbstractWidget
{
    protected function _fieldWidgetInfo()
    {
        return array(
            'field_types' => array($this->_name),
        );
    }

    public function fieldWidgetForm(Field\IField $field, array $settings, $value = null, IEntity $entity = null, array $parents = [], $language = null)
    {      
        if (!$this->_application->HasPermission('directory_admin_directory_' . $field->Bundle->group)) return;
        
        return array(
            '#type' => 'fieldset',
            '#element_validate' => array(array($this, '_fieldWidgetSubmitCallback')),
            'enable' => array(
                '#type' => 'checkbox',
                '#default_value' => !empty($value['value']),
            ),
            'value' => array(
                '#type' => 'select',
                '#title' => __('Priority', 'directories'),
                '#states' => array(
                    'visible' => array(
                        sprintf('[name="%s[enable]"]', $this->_application->Form_FieldName($parents)) => array('type' => 'checked', 'value' => true),
                    ),
                ),
                '#options' => FeaturedFieldType::priorities(),
                '#default_value' => !empty($value['value']) ? $value['value'] : 5,
            ),
            'expires_at' => array(
                '#type' => 'datepicker',
                '#title' => __('End Date', 'directories'),
                '#states' => array(
                    'visible' => array(
                        sprintf('[name="%s[enable]"]', $this->_application->Form_FieldName($parents)) => array('type' => 'checked', 'value' => true),
                    ),
                ),
                '#default_value' => !empty($value['expires_at']) ? $value['expires_at'] : null,
                '#empty_value' => 0,
                '#disable_time' => true,
            ),
        );
    }
    
    public function _fieldWidgetSubmitCallback(Form\Form $form, &$value, $element)
    {
        if (empty($value['enable'])) $value = null;
    }
}