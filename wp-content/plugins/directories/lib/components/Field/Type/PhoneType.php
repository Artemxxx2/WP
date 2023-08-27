<?php
namespace SabaiApps\Directories\Component\Field\Type;

use SabaiApps\Directories\Component\Entity\Model\Bundle;
use SabaiApps\Directories\Component\Entity\Type\IEntity;
use SabaiApps\Directories\Component\Field\IField;

class PhoneType extends AbstractStringType implements ILabellable
{
    protected function _fieldTypeInfo()
    {
        return array(
            'label' => __('Phone Number', 'directories'),
            'default_settings' => array(
                'min_length' => null,
                'max_length' => null,
                'char_validation' => 'none',
                'mask' => '(999) 999-9999',
            ),
            'icon' => 'fas fa-phone',
        );
    }

    public function fieldTypeSettingsForm($fieldType, Bundle $bundle, array $settings, array $parents = [], array $rootParents = [])
    {
        $form = parent::fieldTypeSettingsForm($fieldType, $bundle, $settings, $parents, $rootParents);
        unset($form['char_validation'], $form['regex'], $form['min_length'], $form['max_length']);
        return $form;
    }

    public function fieldSchemaProperties()
    {
        return array('telephone', 'faxNumber');
    }

    public function fieldLabellableLabels(IField $field, IEntity $entity)
    {
        return $entity->getFieldValue($field->getFieldName());
    }
}
