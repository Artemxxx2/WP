<?php
namespace SabaiApps\Directories\Component\FrontendSubmit\FieldWidget;

use SabaiApps\Directories\Component\Field;
use SabaiApps\Directories\Component\Entity;

class GuestFieldWidget extends Field\Widget\AbstractWidget
{
    protected function _fieldWidgetInfo()
    {
        return array(
            'label' => __('Guest Author', 'directories-frontend'),
            'field_types' => array($this->_name),
        );
    }

    public function fieldWidgetForm(Field\IField $field, array $settings, $value = null, Entity\Type\IEntity $entity = null, array $parents = [], $language = null)
    { 
        if (!isset($entity) // do not show on new submission
            || !isset($value) // do not show if no value
            || $entity->getAuthorId() // do not show if not anonymous post
            || !$this->_application->getUser()->isAdministrator()
        ) return;

        return array(
            'name' => array(
                '#type' => 'textfield',
                '#default_value' => isset($value['name']) ? $value['name'] : null,
                '#title' => __('Guest Name', 'directories-frontend'),
                '#required' => true,
                '#weight' => 2,
            ),
            'email' => array(
                '#type' => 'email',
                '#title' => __('E-mail Address', 'directories-frontend'),
                '#default_value' => isset($value['email']) ? $value['email'] : null,
                '#char_validation' => 'email',
                '#weight' => 4,
            ),
            'url' => array(
                '#type' => 'url',
                '#title' => __('Website URL', 'directories-frontend'),
                '#default_value' => isset($value['url']) ? $value['url'] : null,
                '#char_validation' => 'url',
                '#weight' => 10,
            ),
        );
        
        return $ret;
    }
}