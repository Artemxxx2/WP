<?php
namespace SabaiApps\Directories\Component\Entity\FieldWidget;

use SabaiApps\Directories\Component\Entity\Type\IEntity;
use SabaiApps\Directories\Component\Field;

class AuthorFieldWidget extends Field\Widget\AbstractWidget
{
    protected function _fieldWidgetInfo()
    {
        return array(
            'label' => __('Author', 'directories'),
            'field_types' => array($this->_name),
        );
    }

    public function fieldWidgetForm(Field\IField $field, array $settings, $value = null, IEntity $entity = null, array $parents = [], $language = null)
    {
        if (!$this->_application->IsAdministrator()) return;
        
        return array(
            '#title' => __('Author', 'directories'),
            '#type' => 'user',
            '#default_value' => isset($entity) ? $entity->getAuthorId() : $this->_application->getUser()->id,
        );
    }
}