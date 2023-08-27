<?php
namespace SabaiApps\Directories\Component\Social\FieldType;

use SabaiApps\Directories\Component\Field;
use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Application;

class AccountsFieldType extends Field\Type\AbstractType implements
    Field\Type\ISchemable,
    Field\Type\IOpenGraph,
    Field\Type\IHumanReadable,
    Field\Type\IPersonalData,
    Field\Type\ICopiable
{
    protected function _fieldTypeInfo()
    {
        return array(
            'label' => __('Social Accounts', 'directories'),
            'default_renderer' => 'social_accounts',
            'default_settings' => [],
            'icon' => 'fas fa-share-alt',
            'requirable' => false,
        );
    }

    public function fieldTypeSettingsForm($fieldType, Entity\Model\Bundle $bundle, array $settings, array $parents = [], array $rootParents = [])
    {
        $options = [];
        foreach ($this->_application->Social_Medias() as $media_name => $media) {
            $options[$media_name] = isset($media['icon']) ? sprintf('<i class="%s"></i> %s', $this->_application->H($media['icon']), $this->_application->H($media['label'])) : $this->_application->H($media['label']);
        }
        $form = array(
            'medias' => array(
                '#type' => 'sortablecheckboxes',
                '#title' => __('Social medias', 'directories'),
                '#options' => $options,
                '#default_value' => isset($settings['medias']) ? $settings['medias'] : null,
                '#option_no_escape' => true,
            ),
        );
        return $form;
    }

    public function fieldTypeSchema()
    {
        return array(
            'columns' => array(
                'media' => array(
                    'type' => Application::COLUMN_VARCHAR,
                    'length' => 100,
                    'notnull' => true,
                    'was' => 'media',
                    'default' => '',
                ),
                'value' => array(
                    'type' => Application::COLUMN_VARCHAR,
                    'length' => 255,
                    'notnull' => true,
                    'was' => 'value',
                    'default' => '',
                ),
            ),
            'indexes' => array(
                'media' => array(
                    'fields' => array(
                        'media' => array('sorting' => 'ascending'),
                    ),
                    'was' => 'media',
                ),
            ),
        );
    }

    public function fieldTypeOnSave(Field\IField $field, array $values, array $currentValues = null, array &$extraArgs = [])
    {
        $ret = [];
        $value = array_shift($values); // single entry allowed for this field
        if (is_array($value)) {
            foreach ($value as $media => $_value) {
                if (!strlen($_value)) continue;

                $ret[] = array(
                    'media' => $media,
                    'value' => $_value,
                );
            }
        }
        return $ret;
    }

    public function fieldTypeOnLoad(Field\IField $field, array &$values, Entity\Type\IEntity $entity, array $allValues)
    {
        $new_value = [];
        foreach ($values as $value) {
            $new_value[$value['media']] = $value['value'];
        }
        $values = array($new_value);
    }

    public function fieldTypeIsModified(Field\IField $field, $valueToSave, $currentLoadedValue)
    {
        $current = [];
        if (isset($currentLoadedValue[0])) {
            foreach ($currentLoadedValue[0] as $media => $value) {
                $current[] = array('media' => $media, 'value' => $value);
            }
        }
        return $current !== $valueToSave;
    }

    public function fieldSchemaProperties()
    {
        return array('sameAs', 'email');
    }

    public function fieldSchemaRenderProperty(Field\IField $field, $property, Entity\Type\IEntity $entity)
    {
        if (!$value = $entity->getSingleFieldValue($field->getFieldName())) return;

        switch ($property) {
            case 'sameAs':
                $ret = [];
                $medias = $this->_application->Social_Medias();
                foreach ($value as $media_name => $url) {
                    if ($media_name === 'mail'
                        || (!$media = @$medias[$media_name])
                    ) continue;

                    $ret[] = $url;
                }
                return $ret;
            case 'email':
                return array($value['mail']);
        }
    }

    public function fieldOpenGraphProperties()
    {
        return array('article:author', 'books:author', 'music:musician');
    }

    public function fieldOpenGraphRenderProperty(Field\IField $field, $property, Entity\Type\IEntity $entity)
    {
        if ((!$value = $entity->getSingleFieldValue($field->getFieldName()))
            || empty($value['facebook'])
        ) return;

        $medias = $this->_application->Social_Medias();
        if (empty($medias['facebook'])) return;

        return array($value['facebook']);
    }

    public function fieldHumanReadableText(Field\IField $field, Entity\Type\IEntity $entity, $separator = null, $key = null)
    {
        $ret = [];
        if ($values = $entity->getSingleFieldValue($field->getFieldName())) {
            $medias = $this->_application->Social_Medias();
            foreach ($values as $media_name => $value) {
                if (!isset($medias[$media_name])
                    || (!$url = $this->_application->getComponent($medias[$media_name]['component'])->socialMediaUrl($media_name, $value))
                ) continue;

                $ret[] = $medias[$media_name]['label'] . ' - ' . $url;
            }
        }
        return empty($ret) ? '' : implode(isset($separator) ? $separator : PHP_EOL, $ret);
    }

    public function fieldPersonalDataExport(Field\IField $field, Entity\Type\IEntity $entity)
    {
        $ret = [];
        if ($values = $entity->getSingleFieldValue($field->getFieldName())) {
            $medias = $this->_application->Social_Medias();
            foreach ($values as $media_name => $value) {
                if (!isset($medias[$media_name])
                    || !strlen($value)
                ) continue;

                $ret[$media_name] = [
                    'name' => $medias[$media_name]['label'],
                    'value' => $this->_application->getComponent($medias[$media_name]['component'])->socialMediaUrl($media_name, $value),
                ];
            }
        }
        return $ret;
    }

    public function fieldPersonalDataErase(Field\IField $field, Entity\Type\IEntity $entity)
    {
        return true; //delete
    }

    public function fieldCopyValues(Field\IField $field, array $values, array &$allValue, $lang = null)
    {
        return $values;
    }
}
