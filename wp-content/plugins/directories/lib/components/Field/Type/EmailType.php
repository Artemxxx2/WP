<?php
namespace SabaiApps\Directories\Component\Field\Type;

use SabaiApps\Directories\Component\Entity\Type\IEntity;
use SabaiApps\Directories\Component\Entity\Model\Bundle;
use SabaiApps\Directories\Component\Field\IField;
use SabaiApps\Directories\Component\Field\Query;
use SabaiApps\Directories\Component\Form;

class EmailType extends AbstractStringType implements IEmail, IPersonalDataIdentifier, ILabellable
{
    protected function _fieldTypeInfo()
    {
        return array(
            'label' => __('Email', 'directories'),
            'default_settings' => array(
                'min_length' => null,
                'max_length' => null,
                'char_validation' => 'email',
                'check_mx' => false,
            ),
            'icon' => 'far fa-envelope',
        );
    }

    public function fieldTypeSettingsForm($fieldType, Bundle $bundle, array $settings, array $parents = [], array $rootParents = [])
    {
        $form = parent::fieldTypeSettingsForm($fieldType, $bundle, $settings, $parents, $rootParents);
        $form['char_validation']['#type'] = 'hidden';
        $form['char_validation']['#value'] = 'email';
        if (Form\Field\TextField::canCheckMx()) {
            $form['check_mx'] = array(
                '#type' => 'checkbox',
                '#title' => __('Check MX record of e-mail address', 'directories'),
                '#default_value' => $settings['check_mx'],
            );
        }
        return $form;
    }

    public function fieldTypeDefaultValueForm($fieldType, Bundle $bundle, array $settings, array $parents = [])
    {
        return [
            '#type' => 'email',
        ];
    }

    public function fieldSchemaProperties()
    {
        return array('email');
    }

    public function fieldEmailAddress(IField $field, IEntity $entity, $single = true)
    {
        return $single ? $entity->getSingleFieldValue($field->getFieldName()) : $entity->getFieldValue($field->getFieldName());
    }

    public function fieldPersonalDataErase(IField $field, IEntity $entity)
    {
        if (!$field->isFieldRequired()
            || (!$value = $entity->getSingleFieldValue($field->getFieldName()))
        ) return true; // delete

        return $this->_application->getPlatform()->anonymizeEmail($value); // anonymize
    }

    public function fieldPersonalDataQuery(Query $query, $fieldName, $email, $userId)
    {
        $query->fieldIs($fieldName, $email);
    }

    public function fieldPersonalDataAnonymize(IField $field, IEntity $entity)
    {
        return $this->_application->getPlatform()->anonymizeEmail($entity->getSingleFieldValue($field->getFieldName()));
    }

    public function fieldLabellableLabels(IField $field, IEntity $entity)
    {
        return $entity->getFieldValue($field->getFieldName());
    }
}
