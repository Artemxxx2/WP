<?php
namespace SabaiApps\Directories\Component\Field\Type;

use SabaiApps\Directories\Component\Entity\Model\Bundle;
use SabaiApps\Directories\Component\Entity\Type\IEntity;
use SabaiApps\Directories\Component\Field\IField;

class UrlType extends AbstractStringType implements ILinkable, ILabellable
{
    protected function _fieldTypeInfo()
    {
        return array(
            'label' => __('URL', 'directories'),
            'default_settings' => array(
                'min_length' => null,
                'max_length' => null,
                'char_validation' => 'url',
            ),
            'icon' => 'fas fa-link',
        );
    }

    protected function _onSaveValue(IField $field, $value, array $settings)
    {
        if (null !== $value = parent::_onSaveValue($field, $value, $settings)) {
            if (false === strpos($value, '://')) {
                $value = 'https://' . $value;
            }
        }
        return $value;
    }

    public function fieldTypeSettingsForm($fieldType, Bundle $bundle, array $settings, array $parents = [], array $rootParents = [])
    {
        $form = parent::fieldTypeSettingsForm($fieldType, $bundle, $settings, $parents, $rootParents);
        $form['char_validation']['#type'] = 'hidden';
        $form['char_validation']['#value'] = 'url';
        return $form;
    }

    public function fieldTypeDefaultValueForm($fieldType, Bundle $bundle, array $settings, array $parents = [])
    {
        return [
            '#type' => 'url',
        ];
    }

    public function fieldSchemaProperties()
    {
        return array('url');
    }

    public function fieldOpenGraphProperties()
    {
        return array('og:audio', 'og:video', 'books:sample', 'product:product_link');
    }

    public function fieldOpenGraphRenderProperty(IField $field, $property, IEntity $entity)
    {
        if (!$url = $entity->getSingleFieldValue($field->getFieldName())) return;

        return array($url);
    }

    public function fieldPersonalDataErase(IField $field, IEntity $entity)
    {
        if (!$field->isFieldRequired()
            || (!$value = $entity->getSingleFieldValue($field->getFieldName()))
        ) return true; // delete

        return $this->_application->getPlatform()->anonymizeUrl($value); // anonymize
    }

    public function fieldLinkableUrl(IField $field, IEntity $entity, $single = true)
    {
        return $single ? $entity->getSingleFieldValue($field->getFieldName()) : $entity->getFieldValue($field->getFieldName());
    }

    public function fieldLabellableLabels(IField $field, IEntity $entity)
    {
        return $entity->getFieldValue($field->getFieldName());
    }
}
