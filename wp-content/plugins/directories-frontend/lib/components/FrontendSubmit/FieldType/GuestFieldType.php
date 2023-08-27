<?php
namespace SabaiApps\Directories\Component\FrontendSubmit\FieldType;

use SabaiApps\Directories\Component\Field;
use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Application;

class GuestFieldType extends Field\Type\AbstractType implements
    Field\Type\IHumanReadable,
    Field\Type\IPersonalData,
    Entity\FieldType\IPersonalDataAuthorFieldType,
    Field\Type\ICopiable
{
    protected function _fieldTypeInfo()
    {
        return array(
            'label' => __('Guest Author', 'directories-frontend'),
            'creatable' => false,
            'admin_only' => true,
        );
    }

    public function fieldTypeSchema()
    {
        return array(
            'columns' => array(
                'email' => array(
                    'type' => Application::COLUMN_VARCHAR,
                    'notnull' => true,
                    'length' => 255,
                    'was' => 'email',
                    'default' => '',
                ),
                'name' => array(
                    'type' => Application::COLUMN_VARCHAR,
                    'notnull' => true,
                    'length' => 255,
                    'was' => 'name',
                    'default' => '',
                ),
                'url' => array(
                    'type' => Application::COLUMN_VARCHAR,
                    'notnull' => true,
                    'length' => 255,
                    'was' => 'url',
                    'default' => '',
                ),
                'guid' => array(
                    'type' => Application::COLUMN_VARCHAR,
                    'notnull' => true,
                    'length' => 23,
                    'was' => 'guid',
                    'default' => '',
                ),
            )
        );
    }

    public function fieldTypeOnSave(Field\IField $field, array $values, array $currentValues = null, array &$extraArgs = [])
    {
        // Only guest users can add new values
        if (is_null($currentValues)
            && !$this->_application->getUser()->isAnonymous()
        ) return false;

        $ret = [];
        foreach ($values as $key => $value) {
            if (is_array($value)) {
                $value['guid'] = empty($currentValues[$key]['guid']) ? uniqid('', true) : $currentValues[$key]['guid'];
                if (!empty($currentValues[$key])) {
                    $value += $currentValues[$key];
                }
                $ret[] = $value;
            } elseif ($value === false) { // deleting explicitly?
                $ret[] = false;
            }
        }
        return empty($ret) ? false : $ret;
    }

    public function fieldHumanReadableText(Field\IField $field, Entity\Type\IEntity $entity, $separator = null, $key = null)
    {
        $value = $entity->getSingleFieldValue($field->getFieldName());

        $ret = [];
        foreach (array('name', 'email', 'url') as $key) {
            if (!strlen($value[$key])) continue;

            $ret[] = $value[$key];
        }

        return implode(' - ', $ret);
    }

    public function fieldPersonalDataExport(Field\IField $field, Entity\Type\IEntity $entity)
    {
        if (!$value = $entity->getSingleFieldValue($field->getFieldName())) return;

        $ret = [];
        foreach ([
            'name' => __('Guest Name', 'directories-frontend'),
            'email' => __('E-mail Address', 'directories-frontend'),
            'url' => __('Website URL', 'directories-frontend')
        ] as $key => $name) {
            if (!strlen($value[$key])) continue;

            $ret[$key] = ['name' => $name, 'value' => $value[$key]];
        }
        return $ret;
    }

    public function fieldPersonalDataErase(Field\IField $field, Entity\Type\IEntity $entity)
    {
        return true; // delete
    }

    public function fieldPersonalDataQuery(Field\Query $query, $fieldName, $email, $userId)
    {
        $query->fieldIs($fieldName, $email, 'email');
    }

    public function fieldPersonalDataAnonymize(Field\IField $field, Entity\Type\IEntity $entity)
    {
        $value = $entity->getSingleFieldValue($field->getFieldName());
        return [
            'name' => $this->_application->getPlatform()->anonymizeText($value['name']),
            'email' => $this->_application->getPlatform()->anonymizeEmail($value['email']),
            'url' => strlen($value['url']) ? $this->_application->getPlatform()->anonymizeUrl($value['url']) : '',
        ];
    }

    public function fieldCopyValues(Field\IField $field, array $values, array &$allValue, $lang = null)
    {
        return $values;
    }
}
